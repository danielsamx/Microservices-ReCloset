# Guía de defensa del proyecto — ReCloset

> Este documento consolida **todo lo que necesitas saber** para defender el proyecto ReCloset ante un jurado o evaluador. Cubre los **patrones de diseño**, **Prometheus y Grafana**, **Docker**, **CI/CD**, **Kubernetes** y el **cifrado implementado**, con el nivel de detalle suficiente para responder preguntas técnicas con propiedad.

---

## Índice

1. [Visión general del proyecto](#1-visión-general-del-proyecto)
2. [Patrones de diseño](#2-patrones-de-diseño)
3. [Docker y Docker Compose](#3-docker-y-docker-compose)
4. [Prometheus y Grafana — Monitoreo](#4-prometheus-y-grafana--monitoreo)
5. [CI/CD — GitHub Actions](#5-cicd--github-actions)
6. [Kubernetes — Orquestación](#6-kubernetes--orquestación)
7. [Cifrado y seguridad](#7-cifrado-y-seguridad)
8. [Preguntas frecuentes de evaluadores](#8-preguntas-frecuentes-de-evaluadores)

---

## 1. Visión general del proyecto

**ReCloset** es un marketplace de moda de segunda mano construido como una **arquitectura de microservicios**. En lugar de un backend monolítico, el sistema se divide en servicios independientes, cada uno con su propia base de datos, que se comunican entre sí por HTTP.

### ¿Por qué microservicios?

| Ventaja | Explicación concreta en ReCloset |
|---------|----------------------------------|
| **Escalabilidad independiente** | Si el catálogo tiene mucho tráfico pero el chat no, puedo escalar solo `item-service` a 4 réplicas sin tocar los demás. |
| **Despliegue independiente** | Puedo actualizar el chat sin reiniciar la autenticación ni el catálogo. |
| **Aislamiento de fallos** | Si `media-service` cae, el chat y la autenticación siguen funcionando. |
| **Autonomía de equipo** | Cada servicio puede evolucionar su esquema de BD sin coordinar con los demás. |

### Servicios del sistema

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend (Vue 3)                     │
└──────────────────────┬──────────────────────────────────┘
                       │ HTTP / WebSocket
┌──────────────────────▼──────────────────────────────────┐
│              API Gateway (nginx :8080)                  │
│  Punto de entrada único — enruta por prefijo de path    │
└──┬───────────┬───────────┬───────────┬──────────────────┘
   │           │           │           │
┌──▼──┐    ┌───▼──┐   ┌───▼──┐   ┌───▼───┐   ┌─────────┐
│AUTH │    │ITEM  │   │CHAT  │   │MEDIA  │   │REVERB   │
│:8000│    │:8000 │   │:8000 │   │:8000  │   │(WS:8080)│
└──┬──┘    └──┬───┘   └──┬───┘   └──┬────┘   └─────────┘
   │          │          │          │
┌──▼──┐   ┌──▼───┐  ┌───▼──┐  ┌───▼──┐  ┌───────────┐
│auth │   │items │  │chat  │  │media │  │blob volume│
│ db  │   │ db   │  │ db   │  │ db   │  │/var/blob  │
└─────┘   └──────┘  └──────┘  └──────┘  └───────────┘
```

**Cada servicio tiene su propia base de datos PostgreSQL.** Nunca comparten tablas ni hacen queries cruzadas.

---

## 2. Patrones de diseño

Se implementan **3 patrones arquitectónicos** reales, cada uno resolviendo un problema específico de la arquitectura distribuida.

### 2.1 API Gateway

**Archivo clave:** `gateway/nginx.conf`
**Definición en Docker Compose:** líneas 16-34 de `docker-compose.yml`

#### ¿Qué problema resuelve?

Sin un gateway, el frontend tendría que conocer la dirección de cada microservicio: `http://auth:8000`, `http://item:8000`, etc. Esto expone la topología interna, complica CORS (múltiples orígenes) y obliga al frontend a manejar la lógica de enrutado.

#### ¿Cómo se implementa?

Un contenedor **nginx** actúa como punto de entrada único en el puerto `:8080`. Todas las peticiones del frontend van a una sola URL y nginx las enruta internamente:

```
/api/auth/*                    → auth-service:8000
/api/items|catalog|meta|...    → item-service:8000
/api/conversations|messages|...→ chat-service:8000
/api/media/*                   → media-service:8000
/app/* (WebSocket)             → reverb:8080
```

#### ¿Por qué nginx y no otro?

- Es el reverse proxy más probado y eficiente del ecosistema.
- Configuración declarativa (no requiere código).
- Soporta natively WebSocket proxying (para Reverb).
- En producción podemos agregar TLS, rate-limiting y caché sin cambiar los servicios.

#### Posible pregunta del evaluador:

> "¿Y si el gateway se cae, cae todo?"

Sí, es un **punto único de fallo** (SPOF). Por eso en la configuración de Kubernetes el gateway corre con **`replicas: 2`** (archivo `k8s/40-gateway.yaml`), y el Service tipo `LoadBalancer` distribuye tráfico entre ambas réplicas. En producción real se usaría un Ingress Controller (por ejemplo, NGINX Ingress o Traefik) para alta disponibilidad.

---

### 2.2 Database per Service

**Archivos clave:** Los 4 bloques `*-db` en `docker-compose.yml` (líneas 86-258)
**Migraciones:** Cada servicio tiene su directorio `database/migrations/` independiente.

#### ¿Qué problema resuelve?

Una base de datos compartida **acopla** los servicios: un cambio de esquema en la tabla de usuarios podría romper el chat. Además, impide escalar las bases de datos de forma independiente.

#### ¿Cómo se implementa?

| Servicio | Base de datos | Tablas propias |
|----------|---------------|----------------|
| auth-service | `recloset_auth` | users, personal_access_tokens, two_factor_challenges, email_verification_tokens |
| item-service | `recloset_items` | items, item_media, categories, sizes, colors |
| chat-service | `recloset_chat` | conversations, messages, notifications |
| media-service | `recloset_media` | media_files |

#### ¿Pero si el chat necesita datos del usuario?

**No consulta la BD de auth.** En su lugar, hace una llamada HTTP:

```php
// chat-service/app/Support/AuthClient.php
$res = Http::withToken($token)->get('http://auth-service:8000/api/auth/verify');
```

Los datos que se necesitan con frecuencia (como el nombre del dueño de una prenda) se **copian** en la tabla de conversaciones (`owner_name`, `item_title`). Esto es **duplicación controlada** — un trade-off aceptado para evitar dependencias en cada lectura.

#### Posible pregunta del evaluador:

> "Si duplican datos, ¿no hay inconsistencia?"

Sí, la consistencia es **eventual**, no ACID entre servicios. Esto es inherente a los microservicios. Se asume que los datos copiados (nombres, títulos) cambian con poca frecuencia y el impacto de un nombre desactualizado es bajo. Si fuera crítico, se implementaría un sistema de eventos para propagar cambios.

---

### 2.3 Circuit Breaker + Retry

**Archivos clave:**
- `chat-service/app/Support/AuthClient.php` (88 líneas)
- `chat-service/app/Support/ItemClient.php` (29 líneas)

#### ¿Qué problema resuelve?

Cuando un servicio llama a otro por HTTP, pueden ocurrir **fallos transitorios** (la BD del otro servicio aún está arrancando, un pico de latencia) o **fallos sostenidos** (el servicio está caído). Sin protección:
- Los reintentos sin control **saturan** la dependencia caída.
- No reintentar **rompe** por glitches momentáneos.

#### ¿Cómo funciona el Retry?

```
Intento 1 → falla → espera 200ms
Intento 2 → falla → espera 400ms (backoff incremental)
Intento 3 → falla → registra error
```

**Regla clave:** Respuestas definitivas (401 token inválido, 422 validación) **NO** se reintentan. Solo se reintentan errores transitorios (5xx, timeout, conexión rechazada).

#### ¿Cómo funciona el Circuit Breaker?

Es una máquina de estados con 3 fases:

```
   ┌─────────┐     5 fallos       ┌─────────┐     30 seg      ┌──────────────┐
   │ CERRADO │  ──consecutivos──→ │ ABIERTO │  ──expiran──→   │ SEMI-ABIERTO │
   │(normal) │                    │(fail    │                  │ (prueba 1    │
   │         │  ←──éxito────────  │ fast)   │  ←──fallo──────  │  petición)   │
   └─────────┘                    └─────────┘                  └──────────────┘
```

1. **CERRADO**: las peticiones pasan normalmente. Se cuentan los fallos consecutivos.
2. **ABIERTO** (tras 5 fallos): las peticiones fallan **inmediatamente** sin tocar la dependencia (fail-fast). Dura 30 segundos.
3. **SEMI-ABIERTO** (tras 30s): deja pasar 1 petición de prueba. Si tiene éxito, vuelve a CERRADO. Si falla, vuelve a ABIERTO.

#### Implementación en el código:

```php
// AuthClient.php — Estado almacenado en file cache
private function circuitOpen(): bool
{
    return (bool) Cache::store('file')->get('cb_auth_open', false);
}

private function recordFailure(): void
{
    $fails = (int) $c->get('cb_auth_fails', 0) + 1;
    if ($fails >= $this->failThreshold) {  // 5 fallos
        $c->put('cb_auth_open', true, now()->addSeconds(30));  // abrir 30s
    }
}
```

#### Posible pregunta del evaluador:

> "¿El estado del circuit breaker se comparte entre réplicas?"

No, es **por instancia** (almacenado en file cache local). Con varias réplicas en Kubernetes, cada una tiene su propio estado. Para un estado global, se usaría Redis como store compartido. Es una limitación documentada y deliberada para simplicidad.

---

## 3. Docker y Docker Compose

### ¿Qué es Docker y por qué lo usamos?

Docker **empaqueta** cada servicio con todas sus dependencias (PHP 8.3, extensiones, Composer) en una **imagen** aislada. Esto garantiza que el servicio funciona igual en cualquier máquina: mi laptop, la de un compañero, el servidor de producción.

### ¿Qué es Docker Compose?

Es una herramienta que permite **definir y orquestar múltiples contenedores** en un solo archivo (`docker-compose.yml`). En lugar de ejecutar 14 comandos `docker run`, ejecutamos uno solo: `docker compose up -d --build`.

### Estructura del docker-compose.yml

El archivo define **14 servicios** organizados en capas:

```
Capa de presentación:    frontend, gateway
Capa de aplicación:      auth-service, item-service, chat-service, media-service, reverb
Capa de datos:           auth-db, item-db, chat-db, media-db
Capa de observabilidad:  prometheus, grafana
Herramientas de dev:     adminer, filebrowser
```

### Conceptos clave en el docker-compose.yml

#### 1. Extensión YAML (`x-laravel-common`)
```yaml
x-laravel-common: &laravel-common
  restart: unless-stopped
  networks: [recloset]
```
Es una "variable" YAML. Los 4 servicios Laravel la **heredan** con `<<: *laravel-common`, evitando repetir `restart` y `networks` en cada uno. Es un **DRY** (Don't Repeat Yourself) a nivel de infraestructura.

#### 2. Volúmenes (persistencia)
```yaml
volumes:
  auth-db-data:        # datos PostgreSQL de auth
  media-blob-data:     # archivos multimedia subidos
  prometheus-data:     # serie temporal de métricas
  grafana-data:        # dashboards y config de Grafana
```
Los volúmenes **sobreviven** a la destrucción de contenedores. Puedes hacer `docker compose down` (para contenedores) y al volver a levantar, los datos siguen ahí. Solo `docker compose down -v` borra los volúmenes.

#### 3. Health checks y orden de arranque
```yaml
auth-db:
  healthcheck:
    test: ["CMD-SHELL", "pg_isready -U recloset -d recloset_auth"]
    interval: 10s

auth-service:
  depends_on:
    auth-db: { condition: service_healthy }
```
`depends_on` con `condition: service_healthy` **garantiza** que la BD está lista antes de arrancar el servicio Laravel. Sin esto, el servicio intentaría conectarse a una BD que aún está inicializándose y fallaría.

#### 4. Red interna
```yaml
networks:
  recloset:
    driver: bridge
```
Todos los contenedores están en la misma red virtual `recloset`. Se **resuelven por nombre DNS**: `auth-service`, `item-db`, `reverb`, etc. Solo el gateway (`:8080`), el frontend (`:5173`), Prometheus (`:9090`) y Grafana (`:3000`) exponen puertos al host.

### Dockerfiles de los servicios Laravel

Cada servicio Laravel usa la misma estructura de Dockerfile:

```dockerfile
FROM php:8.3-cli                            # Imagen base con PHP 8.3
RUN apt-get install ... && \                # Dependencias del sistema
    docker-php-ext-install pdo_pgsql zip ... # Extensiones PHP
COPY --from=composer:2 ... /usr/bin/composer# Composer para gestionar paquetes
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev               # Instalar dependencias (sin dev)
COPY . .                                    # Copiar código fuente
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
```

El `entrypoint.sh` espera a que la BD esté lista, ejecuta migraciones automáticamente y luego arranca el servidor.

### Posible pregunta del evaluador:

> "¿Por qué no usan un solo contenedor para todo?"

Porque violaría el principio de responsabilidad única. Si el chat necesita actualizarse, ¿por qué reiniciar también la autenticación y el catálogo? Docker + microservicios permite **desplegar, escalar y actualizar** cada parte del sistema de forma independiente.

---

## 4. Prometheus y Grafana — Monitoreo

### ¿Qué es Prometheus?

Prometheus es un sistema de monitoreo **pull-based** (basado en recolección activa). A diferencia de sistemas push-based (donde los servicios envían datos a un colector), Prometheus **visita** periódicamente cada servicio y le pide sus métricas.

### ¿Cómo funciona?

```
Cada 15 segundos:

Prometheus ──GET /metrics──→ auth-service
            ──GET /metrics──→ item-service
            ──GET /metrics──→ chat-service
            ──GET /metrics──→ media-service
            ──GET /health───→ gateway

Prometheus almacena todo en una base de datos de series temporales interna.
```

### ¿Qué métricas recolecta?

Cada servicio Laravel expone un endpoint `/metrics` (implementado en `MetricsController.php`) que devuelve métricas en **formato texto Prometheus**:

| Métrica | Tipo | Qué mide |
|---------|------|----------|
| `recloset_up{service}` | Gauge (0/1) | ¿Está arriba el servicio? |
| `recloset_http_requests_total{service}` | Counter | Total de peticiones HTTP recibidas |
| `recloset_http_errors_total{service}` | Counter | Total de errores 5xx |
| `recloset_http_response_ms_avg{service}` | Gauge | Latencia promedio en milisegundos |

Estas métricas son alimentadas por un **middleware** (`RequestMetrics`) que intercepta cada petición HTTP, mide su duración y clasifica el código de respuesta.

### ¿Qué es PromQL?

Es el lenguaje de consulta de Prometheus. Ejemplos:

```promql
recloset_up                                   # ¿Qué servicios están arriba?
rate(recloset_http_requests_total[5m])        # Peticiones por segundo (últimos 5 min)
recloset_http_errors_total                     # Errores acumulados
recloset_http_response_ms_avg > 500           # Servicios con latencia > 500ms
```

### ¿Qué es Grafana?

Grafana es la **capa de visualización**. No recolecta métricas — se conecta a Prometheus como fuente de datos y permite crear dashboards con gráficos, tablas y alertas.

### ¿Cómo se conectan?

```
Servicios ──/metrics──→ Prometheus ──HTTP API──→ Grafana
                         (recolecta y          (consulta y
                          almacena)              visualiza)
```

### Provisionamiento automático

Todo se configura **sin pasos manuales** al arrancar:

```
monitoring/grafana/
├── provisioning/
│   ├── datasources/prometheus.yml     # Conecta Grafana → Prometheus
│   └── dashboards/dashboards.yml      # Registra la carpeta de dashboards
└── dashboards/
    └── recloset-overview.json         # Dashboard pre-configurado
```

El dashboard **"Visión general de microservicios"** incluye:

| Panel | Qué muestra |
|-------|-------------|
| Disponibilidad de servicios | Indicador ARRIBA/CAÍDO por servicio |
| Solicitudes por segundo | Throughput en los últimos 5 minutos |
| Tiempo de respuesta medio | Latencia promedio en ms |
| Errores 5xx acumulados | Contador de errores del servidor |
| Solicitudes totales | Contador total de peticiones |

### ¿Por qué una sola herramienta (Prometheus) y no ELK, Datadog, etc.?

1. **Simplicidad**: cubre las 5 señales requeridas (disponibilidad, solicitudes, errores, latencia, problemas de comunicación) con un solo componente.
2. **Pull-based**: ideal para contenedores efímeros — no necesita que los servicios se registren en un colector.
3. **Kubernetes nativo**: descubre pods automáticamente por anotaciones (`prometheus.io/scrape: "true"`).
4. **Open-source**: sin costos de licencia.

### Posible pregunta del evaluador:

> "¿Y los logs? ¿No necesitan un ELK stack?"

Los logs son **JSON estructurado** enviados a `stdout/stderr`. En Docker: `docker compose logs -f`. En Kubernetes: `kubectl logs`. No se necesita un sistema adicional porque la recolección de logs ya la provee la plataforma (Docker logging driver / Kubernetes logging). Si se quisiera centralizar logs, se agregaría Loki (del mismo ecosistema Grafana) sin cambiar los servicios.

---

## 5. CI/CD — GitHub Actions

### ¿Qué es CI/CD?

- **CI (Integración Continua)**: Cada vez que alguien hace push o abre un PR, el código se valida automáticamente (lint, tests, build). Si algo falla, el PR se bloquea.
- **CD (Despliegue Continuo)**: Cuando el código pasa todas las validaciones y llega a `main`, se construyen las imágenes Docker y se publican automáticamente, listas para desplegar.

### Flujo del pipeline

```
Developer ──push/PR──→ GitHub ──→ GitHub Actions
                                      │
         ┌────────────────────────────┼───────────────────────────────┐
         ▼                            ▼                               ▼
   test-backend                 test-frontend                  validate-compose
 (lint PHP x4 servicios)      (ESLint + Vitest + build)      (docker compose config)
         └────────────────────────────┼───────────────────────────────┘
                                      ▼  (solo push a main)
                              build-and-push  ──→  GHCR (registro de imágenes)
                                      ▼
                                    deploy   ──→  Kubernetes / entorno real
```

### Cada job explicado

#### 1. `test-backend` (ejecuta 4 veces, una por servicio)

```yaml
strategy:
  matrix:
    service: [auth-service, item-service, chat-service, media-service]
```

Para cada servicio:
1. **Configura PHP 8.3** con las extensiones necesarias.
2. **`composer install`** — instala dependencias.
3. **`php -l`** — Lint de sintaxis. Busca errores de sintaxis PHP en `app/`, `config/`, `routes/`, `database/`.
4. **Laravel Pint** — Lint de estilo. Verifica que el código sigue las convenciones de Laravel.
5. **PHPUnit** — Ejecuta tests automatizados.

#### 2. `test-frontend`

1. **Node 20** + `npm install`.
2. **ESLint** — Lint de JavaScript/Vue.
3. **Vitest** — Tests unitarios del frontend.
4. **`npm run build`** — Compila el frontend con Vite. Si hay errores de TypeScript/Vue, falla aquí.

#### 3. `validate-compose`

```bash
cp .env.example .env
docker compose config > /dev/null
```

Valida que el archivo `docker-compose.yml` es sintácticamente correcto y que todas las variables de entorno están definidas.

#### 4. `build-and-push` (solo en push a main)

- **Depende** de que los 3 jobs anteriores pasen (si alguno falla, no se construyen imágenes).
- Construye una imagen Docker por servicio + frontend usando `docker/build-push-action`.
- **Caché de capas** (`type=gha`) — reutiliza capas de builds anteriores para acelerar.
- Publica en **GHCR** (GitHub Container Registry) con dos tags:
  - `latest` — siempre apunta a la versión más reciente.
  - `sha-abc1234` — tag inmutable vinculado al commit exacto.

#### 5. `deploy` (marcador)

Paso preparado para conectar con el clúster Kubernetes real:
```bash
kubectl set image deployment/<svc> <svc>=ghcr.io/.../svc:sha-xxxx
```

### ¿Qué es GHCR?

GitHub Container Registry. Es un registro de imágenes Docker integrado en GitHub. Las imágenes se almacenan junto al repositorio y se autentican con el `GITHUB_TOKEN`, sin necesidad de Docker Hub.

### Posible pregunta del evaluador:

> "¿Qué pasa si alguien hace push directo a main sin PR?"

El pipeline de CI corre igual en push a main (lint + tests). Si falla, las imágenes no se construyen. En un entorno real, configuraríamos **branch protection rules** para obligar a pasar por PR con checks obligatorios.

---

## 6. Kubernetes — Orquestación

### ¿Qué es Kubernetes y por qué no basta Docker Compose?

**Docker Compose** es perfecto para desarrollo local: un solo comando levanta todo. Pero en **producción** necesitas:

| Necesidad | Docker Compose | Kubernetes |
|-----------|---------------|------------|
| Escalar a N réplicas | Manual (`scale`) | Automático (HPA) |
| Recuperar contenedores caídos | `restart: unless-stopped` | Self-healing + readiness probes |
| Rolling updates (0-downtime) | No nativo | Nativo |
| Secretos seguros | Variables de entorno en .env | Secrets encriptados en etcd |
| Distribución en múltiples nodos | No | Sí |
| Load balancing | No nativo | Service + Ingress |

### Conceptos clave de Kubernetes

#### Pods
La unidad mínima de Kubernetes. Un Pod contiene uno o más contenedores que comparten red y almacenamiento. En nuestro caso, cada Pod tiene un solo contenedor (ej: un Pod con auth-service).

#### Deployments
Declaran el **estado deseado**: "Quiero 2 réplicas de auth-service con esta imagen". Kubernetes se encarga de llegar y mantener ese estado. Si un Pod muere, el Deployment crea otro.

#### Services
Exponen los Pods a la red. Un Service tiene una IP estable y un nombre DNS (`auth-service`). Cuando un Pod muere y se recrea con otra IP, el Service actualiza automáticamente la ruta.

Tipos:
- **ClusterIP** (por defecto): solo accesible dentro del clúster. Para comunicación entre servicios.
- **LoadBalancer**: expone un puerto externo. Para el gateway (`:80`) y Grafana (`:3000`).

#### ConfigMaps
Configuración no sensible en texto plano. Ejemplo:
```yaml
data:
  APP_ENV: production
  LOG_CHANNEL: stderr
  DB_CONNECTION: pgsql
```

#### Secrets
Datos sensibles (contraseñas, API keys). Se almacenan encriptados en etcd (el almacén de estado de Kubernetes).
```yaml
stringData:
  AUTH_APP_KEY: "base64:..."
  DB_PASSWORD: "..."
  INTERNAL_SERVICE_TOKEN: "..."
```

#### PersistentVolumeClaims (PVCs)
Solicitan almacenamiento persistente al clúster. Las bases de datos necesitan que los datos sobrevivan al reinicio del Pod.

#### HorizontalPodAutoscaler (HPA)
Escala automáticamente el número de réplicas basándose en métricas (CPU, memoria).

### Manifiestos del proyecto — explicados

Los archivos en `k8s/` siguen una **numeración por capas**:

| Rango | Capa | Archivos |
|-------|------|----------|
| `00-0x` | Fundación | Namespace, Secrets, ConfigMap |
| `10-1x` | Datos | PostgreSQL Deployments + PVCs |
| `20-2x` | Aplicación | Servicios Laravel + Reverb |
| `30` | Escalado | HPA (autoscaling) |
| `40-4x` | Entrada | Gateway + Frontend |
| `50-5x` | Observabilidad | Prometheus + Grafana |

#### `00-namespace.yaml` — Aislamiento lógico
```yaml
apiVersion: v1
kind: Namespace
metadata:
  name: recloset
```
Todo el proyecto vive en el namespace `recloset`, separado de otros proyectos en el mismo clúster.

#### `20-auth-service.yaml` — Ejemplo de un servicio completo

```yaml
spec:
  replicas: 2                          # 2 réplicas para alta disponibilidad
  template:
    metadata:
      annotations:
        prometheus.io/scrape: "true"   # Prometheus descubre este Pod
        prometheus.io/path: "/metrics"
        prometheus.io/port: "8000"
    spec:
      containers:
        - name: auth-service
          image: recloset/auth-service:latest
          envFrom:
            - configMapRef: { name: recloset-config }  # config no sensible
          env:
            - name: DB_PASSWORD
              valueFrom:
                secretKeyRef: { name: recloset-secrets, key: DB_PASSWORD }  # secreto

          readinessProbe:              # ¿Está listo para recibir tráfico?
            httpGet: { path: /health, port: 8000 }
            initialDelaySeconds: 15
          livenessProbe:               # ¿Sigue vivo?
            httpGet: { path: /health, port: 8000 }
            initialDelaySeconds: 30

          resources:                   # Límites de recursos
            requests: { cpu: "100m", memory: "192Mi" }  # mínimo garantizado
            limits: { cpu: "500m", memory: "512Mi" }    # máximo permitido
```

**Readiness vs Liveness Probe:**
- **Readiness**: "¿Puedo enviarle tráfico?" — Si falla, Kubernetes **deja de enviar tráfico** pero no reinicia el Pod. Útil durante migraciones de BD.
- **Liveness**: "¿Está vivo?" — Si falla, Kubernetes **reinicia el Pod**. Detecta deadlocks o procesos congelados.

#### `30-hpa.yaml` — Escalado automático

```yaml
spec:
  scaleTargetRef:
    kind: Deployment
    name: item-service
  minReplicas: 2          # nunca menos de 2
  maxReplicas: 6          # nunca más de 6
  metrics:
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 70   # si la CPU media > 70%, escala
```

Cuando el tráfico sube, el HPA añade Pods. Cuando baja, los elimina. **Cada microservicio escala de forma independiente.**

### Posible pregunta del evaluador:

> "¿Realmente ejecutaron esto en un clúster?"

Los manifiestos están diseñados para ejecutarse en **Minikube** (Kubernetes local). El documento `KUBERNETES.md` incluye la guía paso a paso. En el CI/CD, el job `deploy` está preparado para conectarse a un clúster real (requiere configurar `KUBE_CONFIG` como secret del repositorio).

---

## 7. Cifrado y seguridad

### ¿Qué se cifra y por qué?

| Dato | Dónde | Cómo | Por qué |
|------|-------|------|---------|
| **Contraseñas** | auth-service BD | `bcrypt` (Hash, irreversible) | Estándar de la industria. Si la BD se filtra, las contraseñas no son legibles. |
| **Código OTP (6 dígitos)** | auth-service BD | `bcrypt` (Hash) | El código se envía por email y se verifica contra el hash. Nunca se almacena en texto plano. |
| **Token de challenge 2FA** | auth-service BD | **AES-256-CBC** (`Crypt::encryptString`) | El token público se cifra antes de almacenar. Se descifra solo para comparar al verificar. |
| **Mensajes de chat** | chat-service BD | **AES-256-CBC** (cast `EncryptedCast`) | Los mensajes se cifran automáticamente al escribir en la BD y se descifran al leer. |
| **Notificaciones (título, cuerpo, datos)** | chat-service BD | **AES-256-CBC** (cast `EncryptedCast` / `encrypted:array`) | Protege contenido sensible de las notificaciones en reposo. |
| **Asunto de correo OTP** | email | Eliminado del subject | El código OTP ya no aparece en el asunto del correo para evitar exposición en previews. |

### Diferencia entre cifrado y hashing

| | Cifrado (AES-256) | Hashing (bcrypt) |
|---|---|---|
| **Reversible** | ✅ Sí, con la clave | ❌ No, es irreversible |
| **Propósito** | Proteger datos que necesitas leer después (mensajes, tokens) | Proteger datos que solo necesitas comparar (contraseñas, OTPs) |
| **Clave** | `APP_KEY` del servicio | Salt aleatorio interno |

### ¿Cómo funciona el cifrado AES-256-CBC en Laravel?

```php
// Al guardar un mensaje:
$ciphertext = Crypt::encryptString('Hola, ¿aún vendes la chaqueta?');
// Resultado en BD: "eyJpdiI6InV4R..." (base64 de IV + ciphertext + HMAC)

// Al leer el mensaje:
$plaintext = Crypt::decryptString($ciphertext);
// Resultado: "Hola, ¿aún vendes la chaqueta?"
```

El cast `EncryptedCast` hace esto **automáticamente** al asignar y leer atributos del modelo Eloquent.

### Retrocompatibilidad con datos legacy

```php
// EncryptedCast.php — método get()
try {
    return Crypt::decryptString($value);
} catch (DecryptException) {
    // Dato legacy no cifrado: devolver tal cual
    return $value;
}
```

Si hay mensajes antiguos almacenados sin cifrar, el cast los devuelve tal cual sin romper la aplicación.

### Seguridad adicional ya implementada

- **Fortaleza de contraseña**: `Password::min(8)->mixedCase()->numbers()` — mínimo 8 caracteres, mayúsculas, minúsculas y números.
- **Introspección de token**: los servicios no confían en el token del cliente; lo validan contra auth-service.
- **Token de servicio (`X-Internal-Token`)**: secreto compartido para llamadas máquina-a-máquina, nunca expuesto al navegador.
- **Sanitización de archivos**: se valida MIME detectado por el servidor, no el declarado por el cliente.
- **Sin stack traces en producción**: `APP_DEBUG=false` oculta información interna en las respuestas de error.
- **Eloquent parametrizado**: sin SQL concatenado, previniendo inyección SQL.

---

## 8. Preguntas frecuentes de evaluadores

### Arquitectura

**P: ¿Por qué microservicios y no un monolito?**
R: ReCloset tiene 4 dominios de negocio claros (autenticación, catálogo, chat, multimedia) con requisitos de escalado diferentes. El catálogo puede necesitar 4 réplicas mientras el chat necesita 2. Un monolito obligaría a escalar todo junto.

**P: ¿Cómo manejan la consistencia entre servicios?**
R: Consistencia eventual. Los datos compartidos (nombre de usuario, título de prenda) se copian en la tabla que los necesita y se asume que cambian con poca frecuencia. Para datos críticos (autenticación), se consulta al servicio dueño en tiempo real.

**P: ¿Y si un servicio cae?**
R: El Circuit Breaker hace fail-fast (no satura la dependencia caída). El servicio consumidor devuelve un error controlado. Kubernetes reinicia el Pod caído automáticamente (liveness probe). Los demás servicios siguen funcionando.

### Docker

**P: ¿Cuántos contenedores tiene el sistema?**
R: 14 en desarrollo (4 servicios + 4 BDs + gateway + frontend + reverb + prometheus + grafana + adminer/filebrowser).

**P: ¿Qué pasa si hago `docker compose down`?**
R: Los contenedores se destruyen pero los volúmenes (datos) persisten. Al volver a levantar, los datos están intactos. Solo `docker compose down -v` borra los datos.

### Monitoreo

**P: ¿Cómo saben si un servicio está caído?**
R: Prometheus sondea `/health` cada 15 segundos. Si un servicio no responde, aparece como `up=0` en Prometheus y en el dashboard de Grafana. Se puede configurar alerting con reglas de Prometheus.

**P: ¿Qué pasa si Prometheus se cae?**
R: Se pierden las métricas que no se recolectan durante el downtime, pero los servicios siguen funcionando normalmente. Prometheus no es crítico para el funcionamiento de la aplicación.

### CI/CD

**P: ¿Qué dispara el pipeline?**
R: Un push o PR a la rama `main`. En PR: solo validación y tests. En push a main: validación + build de imágenes + deploy.

**P: ¿Dónde se almacenan las imágenes Docker?**
R: En GitHub Container Registry (GHCR), vinculado al repositorio. La autenticación usa el `GITHUB_TOKEN` integrado de GitHub Actions.

### Kubernetes

**P: ¿Cuál es la diferencia entre Docker Compose y Kubernetes?**
R: Docker Compose es para desarrollo local (un solo host). Kubernetes es para producción: distribución en múltiples nodos, autoescalado, rolling updates sin downtime, self-healing, y secretos seguros.

**P: ¿Qué es un HPA?**
R: HorizontalPodAutoscaler. Monitorea la CPU de los Pods y agrega/quita réplicas automáticamente. En nuestro caso: `item-service` y `chat-service` escalan entre 2 y 6 réplicas si la CPU media supera el 70%.

### Seguridad

**P: ¿Cómo protegen las contraseñas?**
R: Hash bcrypt irreversible via el cast `hashed` de Eloquent. Nunca se almacena ni se registra en logs la contraseña en texto plano.

**P: ¿Qué cifrado usan en los mensajes?**
R: AES-256-CBC (estándar de la industria, el mismo que usa HTTPS) con la `APP_KEY` de cada servicio como clave. El cifrado es transparente: se aplica automáticamente al escribir en la BD y se descifra al leer, gracias al cast `EncryptedCast` de Eloquent.

**P: ¿El OTP se almacena en texto plano?**
R: No. El código de 6 dígitos se hashea con bcrypt (`Hash::make`). El token del challenge se cifra con AES-256-CBC (`Crypt::encryptString`). Además, el código ya no aparece en el asunto del correo electrónico para evitar exposición en previews y notificaciones push.
