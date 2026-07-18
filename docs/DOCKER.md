# Docker

Todo el sistema está dockerizado. `docker-compose.yml` levanta: frontend, los 4 microservicios Laravel, sus 4 PostgreSQL, el servidor Reverb, el gateway y Prometheus.

## Construcción y ejecución

```bash
cp .env.example .env      # variables de entorno (no se commitea .env real)
make keys                 # genera APP_KEY por servicio -> pégalos en .env
docker compose up -d --build
```

## Detención

```bash
docker compose down       # detiene y elimina contenedores (los volúmenes PERSISTEN)
docker compose down -v    # además borra volúmenes (¡elimina datos y blobs!)
```

## Logs (estructurados en JSON)

```bash
docker compose logs -f                 # todos
docker compose logs -f item-service    # uno
```

## Volúmenes (persistencia)

| Volumen | Contenido |
|---------|-----------|
| `auth-db-data`, `item-db-data`, `chat-db-data`, `media-db-data` | Datos PostgreSQL |
| `media-blob-data` | **Blobs multimedia** (`/var/blobstore`) |
| `prometheus-data` | Serie temporal de métricas |

Sobreviven a reinicios y recreación de contenedores.

## Redes

Una red *bridge* `recloset`. Los servicios se resuelven por nombre DNS interno (`auth-service`, `item-db`, `reverb`, …). Solo el gateway (`8080`), el frontend (`5173`) y Prometheus (`9090`) publican puertos al host.

## Health checks y dependencias

- Cada PostgreSQL: `pg_isready`.
- Cada servicio Laravel: petición a `/health`.
- Gateway: `wget /health`.
- `depends_on: condition: service_healthy` asegura el orden de arranque (BD → servicio → gateway).

## Dockerfiles

- **Servicios Laravel**: `php:8.3-cli` + extensiones (`pdo_pgsql`, `zip`, `pcntl`, `bcmath`), `composer install`, `entrypoint.sh` que espera la BD, migra/siembra y arranca `php artisan serve` (o el comando de Reverb).
- **Frontend**: `node:20-alpine`, `npm install`, `vite` en modo host.
- **Gateway/Prometheus**: imágenes oficiales + configuración montada.
