# Monitoreo y observabilidad — Prometheus

## Herramienta seleccionada y justificación

Se usa **una única herramienta: Prometheus**. Razones:

- Cubre todas las señales pedidas por *scraping* de un endpoint estándar: **disponibilidad** (`recloset_up`), **solicitudes** (`recloset_http_requests_total`), **errores** (`recloset_http_errors_total`), **tiempo de respuesta** (`recloset_http_response_ms_avg`) y **problemas de comunicación** (un servicio caído aparece como `up=0` / target *down*).
- Es *pull-based*, ideal para microservicios efímeros: descubre y sondea `/metrics` y `/health` sin acoplar los servicios a un colector.
- Trae su propia UI y lenguaje de consulta (**PromQL**) y alertas, evitando sumar más herramientas.
- Se integra nativamente con Kubernetes (descubrimiento por anotaciones de pod).

Los **logs** son estructurados en JSON a `stdout/stderr` (canal `stderr`), recolectables por `docker compose logs` o por el *logging* de Kubernetes, sin añadir un segundo sistema.

## Configuración

- Compose: `monitoring/prometheus.yml` sondea `/metrics` de cada servicio y `/health` del gateway (`http://localhost:9090`).
- Kubernetes: `k8s/50-prometheus.yaml` descubre pods con las anotaciones `prometheus.io/scrape|path|port`.
- Cada servicio Laravel expone:
  - `GET /health` → estado + chequeo de BD (`200`/`503`).
  - `GET /metrics` → formato texto Prometheus, alimentado por el middleware `RequestMetrics`.

## Datos recopilados

```
recloset_up{service}                     # 1 disponible / 0 caído
recloset_http_requests_total{service}    # contador de peticiones
recloset_http_errors_total{service}      # contador de 5xx
recloset_http_response_ms_avg{service}   # latencia media (ms)
```

## Cómo se consulta

Abrir `http://localhost:9090` y usar PromZQL, por ejemplo:

```promql
recloset_up                                   # qué servicios están arriba
rate(recloset_http_requests_total[5m])        # throughput por servicio
recloset_http_errors_total                     # errores acumulados
recloset_http_response_ms_avg                  # latencia media
```

En **Targets** (`Status → Targets`) se ve qué servicios responden y cuáles están *down* (problema de comunicación/disponibilidad).

## Logs estructurados

Cada línea de log es JSON e incluye: `service`, fecha/hora (`datetime`), nivel (`level`), `message` y contexto (`context`/`extra`). Ejemplo:

```json
{"message":"item.created","level":"INFO","datetime":"2026-07-17T20:41:00Z","extra":{"service":"ReCloset","env":"production"},"context":{"item_id":12,"owner":3}}
```

**No se registran** contraseñas, tokens ni claves. Los identificadores sensibles (email en login fallido) se *hashean* antes de loggear.

---

## Visualización con Grafana

Prometheus recolecta y almacena las métricas; **Grafana** las visualiza en dashboards. Grafana se añadió al stack (`docker-compose.yml`, servicio `grafana`) y toma Prometheus como fuente de datos.

### Acceso

| URL | Servicio | Credenciales |
|-----|----------|--------------|
| http://localhost:3000 | Grafana | `GRAFANA_ADMIN_USER` / `GRAFANA_ADMIN_PASSWORD` (por defecto `admin` / `admin`) |
| http://localhost:9090 | Prometheus | — |

Cambia la contraseña por defecto en el `.env` antes de exponerlo.

### Provisionamiento automático

Todo se configura solo al arrancar (sin pasos manuales), mediante archivos montados:

```
monitoring/grafana/
├── provisioning/
│   ├── datasources/prometheus.yml     # conecta Grafana -> Prometheus (http://prometheus:9090)
│   └── dashboards/dashboards.yml      # registra la carpeta de dashboards
└── dashboards/
    └── recloset-overview.json         # dashboard principal
```

- **Datasource:** `Prometheus` queda como fuente por defecto, apuntando al servicio interno `prometheus:9090`.
- **Dashboard:** `ReCloset · Visión general de microservicios` se carga automáticamente en la carpeta *ReCloset*.

### Dashboard "Visión general de microservicios"

Incluye, con un selector de servicio (`$service`):

| Panel | Métrica / consulta |
|-------|--------------------|
| Disponibilidad de servicios | `recloset_up` (ARRIBA/CAÍDO por servicio) |
| Solicitudes por segundo | `rate(recloset_http_requests_total[5m])` |
| Tiempo de respuesta medio | `recloset_http_response_ms_avg` (ms) |
| Errores 5xx acumulados | `recloset_http_errors_total` |
| Solicitudes totales | `recloset_http_requests_total` |

### Puesta en marcha

```bash
docker compose up -d prometheus grafana
# Abre http://localhost:3000 -> inicia sesión -> carpeta "ReCloset" -> dashboard
```

### En Kubernetes

`k8s/51-grafana.yaml` despliega Grafana con el datasource y el provider de dashboards como `ConfigMap`, y un `Service` tipo `LoadBalancer` en el puerto 3000. La contraseña de admin se toma del `Secret` `recloset-secrets` (`GRAFANA_ADMIN_PASSWORD`).

```bash
kubectl apply -f k8s/51-grafana.yaml
```

> Nota sobre "una única herramienta": Prometheus sigue siendo el motor de métricas y alertas; Grafana es la **capa de visualización** encima de esos mismos datos (no un segundo sistema de recolección).
