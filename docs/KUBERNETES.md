# Guía detallada de ejecución y arquitectura de Kubernetes en ReCloset

> Este documento detalla la arquitectura de Kubernetes (K8s) implementada en el proyecto ReCloset y proporciona un manual paso a paso de ejecución, administración e inspección del clúster local (usando Minikube).

---

## 1. Conceptos Fundamentales en la Arquitectura de ReCloset

En producción, Docker Compose no es suficiente debido a la necesidad de **orquestación elástica**, **autoescalado**, **auto-recuperación (self-healing)** y **gestión segura de secretos**. Los manifiestos incluidos en la carpeta `k8s/` trasladan nuestra topología de microservicios a objetos nativos de Kubernetes.

### Mapeo de Componentes

| Recurso Kubernetes | Propósito en ReCloset | Ubicación de Manifiestos |
|--------------------|-----------------------|-------------------------|
| **Namespace** | Aislamiento lógico del proyecto. Evita colisiones de nombres. | `00-namespace.yaml` |
| **Secrets** | Credenciales sensibles codificadas (claves de app, claves de BD, etc.). | `01-secrets.yaml` |
| **ConfigMaps** | Variables de entorno no confidenciales compartidas por los microservicios. | `02-configmap.yaml`, `42-gateway-configmap.yaml` |
| **PersistentVolumeClaims (PVC)** | Solicitudes de almacenamiento persistente que sobreviven a la recreación de los Pods (para BDs y blobs). | `10-db-*.yaml`, `11-media-pvc.yaml` |
| **Deployments** | Declaración del estado deseado (imagen Docker, número de réplicas, recursos) para cada microservicio. | `20-*.yaml`, `21-reverb.yaml`, `40-gateway.yaml`, `41-frontend.yaml` |
| **Services** | Abstracción de red que provee una IP interna y balanceo de carga estable hacia los Pods. | Enlazados dentro de los archivos `20-*.yaml`, `10-db-*.yaml`, etc. |
| **HorizontalPodAutoscaler (HPA)** | Escalado automático de réplicas basado en consumo de CPU. | `30-hpa.yaml` |

---

## 2. Preparación del Entorno Local

Para ejecutar estos manifiestos localmente, necesitas instalar:
1. **Docker Desktop** o **Docker Engine**.
2. **Minikube** (un clúster Kubernetes local de un solo nodo).
3. **kubectl** (la herramienta de línea de comandos para interactuar con el clúster).

### Paso 1: Iniciar Minikube

Ejecuta el siguiente comando para arrancar el clúster local indicando los recursos sugeridos:
```bash
minikube start --cpus=4 --memory=8192 --disk-size=20g
```
*Esto inicia una máquina virtual o contenedor local configurado como nodo de Kubernetes.*

### Paso 2: Redirigir el entorno Docker a Minikube

Por defecto, los comandos `docker build` crean imágenes en el Docker local de tu host. Debes indicarle a tu consola que use el demonio Docker interno de Minikube, para que Kubernetes pueda ver las imágenes sin necesidad de subirlas a un registro externo (como Docker Hub):
- **Windows (PowerShell)**:
  ```powershell
  & minikube -p minikube docker-env | Invoke-Expression
  ```
- **Linux / macOS / Git Bash**:
  ```bash
  eval $(minikube docker-env)
  ```

---

## 3. Construcción de Imágenes Docker

Con la terminal apuntando al Docker de Minikube, construye las imágenes de todos los microservicios y del frontend:

```bash
# Desde la raíz del proyecto ReCloset:
docker build -t recloset/auth-service:latest  services/auth-service
docker build -t recloset/item-service:latest  services/item-service
docker build -t recloset/chat-service:latest  services/chat-service
docker build -t recloset/media-service:latest services/media-service
docker build -t recloset/frontend:latest      frontend
```

---

## 4. Despliegue Paso a Paso en Kubernetes

Sigue este orden estrictamente para garantizar que los secretos y la configuración existan antes de que los servicios intenten leerlos.

### Paso 1: Crear el Namespace
```bash
kubectl apply -f k8s/00-namespace.yaml
```
*Crea el namespace `recloset` para aislar nuestros recursos.*

### Paso 2: Configurar Secretos y Ajustes No Sensibles
1. Abre [01-secrets.yaml](file:///c:/Users/danie/Documents/ReCloset/k8s/01-secrets.yaml) y define valores reales (codificados o en stringData para facilidad de desarrollo).
2. Aplica secretos y configmaps:
```bash
kubectl apply -f k8s/01-secrets.yaml
kubectl apply -f k8s/02-configmap.yaml
```

### Paso 3: Desplegar Bases de Datos y Almacenamiento Persistente
```bash
# Aplica las bases de datos de cada servicio (incluye PVC, Deployment y Service por BD)
kubectl apply -f k8s/10-db-auth.yaml
kubectl apply -f k8s/10-db-chat.yaml
kubectl apply -f k8s/10-db-item.yaml
kubectl apply -f k8s/10-db-media.yaml

# Aplica la solicitud de volumen persistente para almacenamiento multimedia
kubectl apply -f k8s/11-media-pvc.yaml
```

### Paso 4: Crear ConfigMap para el Gateway (nginx)
El gateway necesita leer los archivos de configuración de nginx. Creamos un ConfigMap directamente desde los archivos locales:
```bash
kubectl -n recloset create configmap gateway-nginx \
  --from-file=default.conf=gateway/nginx.conf \
  --from-file=proxy_common.conf=gateway/proxy_common.conf
```

### Paso 5: Desplegar Microservicios y Frontend
```bash
# Aplica servicios principales
kubectl apply -f k8s/20-auth-service.yaml
kubectl apply -f k8s/20-item-service.yaml
kubectl apply -f k8s/20-chat-service.yaml
kubectl apply -f k8s/20-media-service.yaml

# Aplica servidor WebSocket (Laravel Reverb)
kubectl apply -f k8s/21-reverb.yaml

# Aplica Frontend y API Gateway
kubectl apply -f k8s/41-frontend.yaml
kubectl apply -f k8s/40-gateway.yaml
```

### Paso 6: Configurar Monitoreo y Escalado (HPA)
```bash
# Autoescalado horizontal
kubectl apply -f k8s/30-hpa.yaml

# Monitoreo (Prometheus + Grafana)
kubectl apply -f k8s/50-prometheus.yaml
kubectl apply -f k8s/51-grafana.yaml
```

---

## 5. Verificación e Inspección del Clúster

Una vez desplegado todo, verifica el estado de salud de los recursos.

### 5.1 Verificar Pods y Servicios
```bash
# Ver que todos los Pods cambien a estado RUNNING y HEALTHY (2/2 o 1/1 ready)
kubectl -n recloset get pods

# Listar servicios para ver las IPs asignadas
kubectl -n recloset get svc
```

### 5.2 Liveness y Readiness Probes (Salud de los Pods)
Cada microservicio tiene configurados dos Probes en su manifiesto:
- **Readiness Probe**: Apunta a `/health`. Si la base de datos está caída, responde 503 y Kubernetes saca el Pod del balanceador del Service para que ningún usuario sufra errores.
- **Liveness Probe**: Apunta a `/health`. Si responde con error repetidamente, Kubernetes asume que el proceso está colgado y reinicia el Pod automáticamente.

Puedes verificar el estado detallado de un Pod específico con:
```bash
kubectl -n recloset describe pod <nombre-del-pod>
# Busca la sección "Liveness" y "Readiness" en la salida.
```

### 5.3 Ver logs de un Microservicio en Tiempo Real
```bash
kubectl -n recloset logs -f deploy/chat-service
```

---

## 6. Acceder a la Aplicación desde el Navegador

Dado que Minikube corre dentro de una VM aislada, las IPs del Service tipo `LoadBalancer` no son directamente accesibles en tu localhost. Minikube nos provee un comando para enrutar el tráfico:

### Opción A: Crear túneles (Recomendado)
Abre otra terminal y ejecuta:
```bash
minikube tunnel
```
*Este comando expone las direcciones externas de los servicios tipo `LoadBalancer`. Podrás abrir el Frontend en `http://localhost` (puerto 80 enrutable a través del gateway).*

### Opción B: Obtener URL directa por servicio
Si no quieres usar el túnel, puedes pedirle a Minikube la IP y puerto temporal expuesto:
```bash
minikube service gateway -n recloset --url
```

---

## 7. Pruebas de Escalado y Resiliencia

Para demostrar la robustez de la arquitectura frente al jurado:

### 7.1 Simular Alta Carga y Ver HPA
El Horizontal Pod Autoscaler (`30-hpa.yaml`) monitorea el consumo de CPU de `item-service` y `chat-service`. Puedes ver el HPA actual con:
```bash
kubectl -n recloset get hpa
```
Si simulas peticiones concurrentes (por ejemplo, con `ab` de Apache o `wrk`), verás que el número de réplicas sube automáticamente desde 2 hasta un máximo de 6 Pods.

### 7.2 Escalado Manual Inmediato
Si sabes que habrá un pico de tráfico programado (ej. Black Friday) y quieres escalar un servicio de antemano:
```bash
kubectl -n recloset scale deployment item-service --replicas=5
# Kubernetes creará instantáneamente 3 Pods adicionales para llegar a las 5 réplicas declaradas.
```

### 7.3 Demostrar Resiliencia y Self-Healing
Elimina un Pod del auth-service manualmente y observa cómo Kubernetes crea uno de inmediato:
```bash
# Obtén los pods
kubectl -n recloset get pods

# Elimina uno
kubectl -n recloset delete pod auth-service-xxxx-yyyy

# Observa en tiempo real
kubectl -n recloset get pods -w
# Verás que el Pod viejo pasa a Terminating y un Pod nuevo se crea y pasa a Running al instante.
```
