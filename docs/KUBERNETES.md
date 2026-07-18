# Kubernetes

Kubernetes **es técnicamente viable** y está implementado en `k8s/`. Docker Compose se mantiene para desarrollo local; los manifiestos K8s son para orquestación y escalado independiente.

## Recursos incluidos

| Archivo | Recursos |
|---------|----------|
| `00-namespace.yaml` | Namespace `recloset` |
| `01-secrets.yaml` | `Secret` (APP_KEYs, DB_PASSWORD, REVERB_APP_SECRET, INTERNAL_SERVICE_TOKEN) |
| `02-configmap.yaml` | `ConfigMap` con configuración no sensible |
| `10-db-*.yaml` | 4 × (PostgreSQL Deployment + **PVC** + Service) con liveness/readiness `pg_isready` |
| `11-media-pvc.yaml` | **PVC** del blob store |
| `20-*.yaml` | Deployments + Services de auth/item/chat/media (probes, envFrom, secretKeyRef, límites) |
| `21-reverb.yaml` | Deployment + Service del WebSocket |
| `30-hpa.yaml` | **HorizontalPodAutoscaler** de item y chat (escalado independiente) |
| `40-gateway.yaml` | Deployment (2 réplicas) + Service `LoadBalancer` |
| `41-frontend.yaml` | Deployment + Service |
| `50-prometheus.yaml` | Prometheus con descubrimiento de pods por anotaciones |

Incluye lo pedido: **Deployments, Services, ConfigMaps, Secrets, PersistentVolumes/Claims, Liveness y Readiness Probes**, y escalado independiente vía HPA + `kubectl scale`.

## Despliegue

```bash
# 1) Construir imágenes con los nombres esperados por los manifiestos
docker build -t recloset/auth-service:latest  services/auth-service
docker build -t recloset/item-service:latest  services/item-service
docker build -t recloset/chat-service:latest  services/chat-service
docker build -t recloset/media-service:latest services/media-service
docker build -t recloset/frontend:latest      frontend
# (en minikube: eval $(minikube docker-env) antes de construir)

# 2) ConfigMap del gateway a partir de los archivos fuente
kubectl -n recloset create configmap gateway-nginx \
  --from-file=default.conf=gateway/nginx.conf \
  --from-file=proxy_common.conf=gateway/proxy_common.conf

# 3) Aplicar todo (edita 01-secrets.yaml antes)
kubectl apply -f k8s/
```

## Escalado independiente

```bash
kubectl -n recloset scale deployment item-service --replicas=4
kubectl -n recloset get hpa
```

Cada microservicio escala por separado. **Nota**: el `media-blob-pvc` es `ReadWriteOnce`; para escalar media-service horizontalmente con almacenamiento compartido, cambiar a un volumen `ReadWriteMany` (NFS/CephFS) o a un backend de objetos distribuido.

## Administración

```bash
kubectl -n recloset get pods
kubectl -n recloset logs -f deploy/chat-service
kubectl -n recloset describe hpa item-service-hpa
```
