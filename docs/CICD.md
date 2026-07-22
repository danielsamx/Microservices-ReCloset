# CI/CD — Integración y despliegue continuo

Pipeline de **GitHub Actions** definido en `.github/workflows/ci-cd.yml`. Automatiza validación, pruebas, construcción de imágenes Docker y publicación en el registro, con un paso de despliegue conectable al clúster.

## Flujo general

```
Developer ──push/PR──> GitHub ──> GitHub Actions
                                     │
        ┌────────────────────────────┼────────────────────────────┐
        ▼                            ▼                            ▼
  test-backend                 test-frontend               validate-compose
 (lint PHP x4)                 (vite build)               (docker compose config)
        └────────────────────────────┼────────────────────────────┘
                                     ▼  (solo push a main)
                             build-and-push  ──>  GHCR (ghcr.io/owner/repo/<servicio>)
                                     ▼
                                   deploy   ──>  Kubernetes / entorno real
```

## Disparadores

| Evento | Qué corre |
|--------|-----------|
| Pull request a `main` | Validación y pruebas (no publica imágenes) |
| Push a `main` | Validación + build & push de imágenes + despliegue |

## Jobs

### 1. `test-backend` (matriz por servicio)
Para `auth-service`, `item-service`, `chat-service`, `media-service`:
- Configura PHP 8.3 + Composer.
- `composer install`.
- **Lint de sintaxis** con `php -l` sobre `app/`, `config/`, `routes/`, `database/`.
- Ejecuta pruebas si existen (`php artisan test` / PHPUnit); si no, lo indica (pendiente).

### 2. `test-frontend`
- Node 20 + `npm ci`.
- `npm run build` (compila el frontend con Vite; falla si hay errores de compilación).

### 3. `validate-compose`
- `docker compose config` sobre el `.env.example` para verificar que el stack es válido.

### 4. `build-and-push` (solo push a `main`)
- Depende de que los tres anteriores pasen.
- Construye una imagen por servicio + frontend (matriz) con `docker/build-push-action` y **caché de capas** (`type=gha`).
- Publica en **GitHub Container Registry** con tags `latest` y `sha-<commit>`:
  `ghcr.io/<owner>/<repo>/auth-service`, `.../item-service`, `.../chat-service`, `.../media-service`, `.../frontend`.
- Autenticación con el `GITHUB_TOKEN` integrado (permiso `packages: write`).

### 5. `deploy` (marcador)
Paso preparado para el despliegue real. Opciones típicas:
- `kubectl set image deployment/<svc> <svc>=ghcr.io/.../<svc>:sha-xxxx -n recloset` (rolling update), o
- `kubectl apply -f k8s/` contra el clúster.

Requiere añadir el acceso al clúster como secret (`KUBE_CONFIG`) y descomentar/definir los comandos según el entorno.

## Configuración necesaria en el repositorio

| Secret / ajuste | Uso |
|-----------------|-----|
| `GITHUB_TOKEN` | Provisto automáticamente por Actions (login y push a GHCR) |
| `KUBE_CONFIG` (opcional) | Kubeconfig del clúster para el job `deploy` |
| Visibilidad de paquetes | En *Settings → Packages*, define si las imágenes GHCR son públicas o privadas |

## Ejecutar los mismos pasos en local

```bash
# Lint backend
for s in auth-service item-service chat-service media-service; do
  (cd services/$s && composer install && find app config routes database -name "*.php" -print0 | xargs -0 -n1 php -l)
done

# Build frontend
(cd frontend && npm ci && npm run build)

# Validar compose
docker compose config >/dev/null && echo OK
```

## Trabajo futuro sugerido

- Añadir pruebas automatizadas (PHPUnit/Pest en backend, Vitest en frontend) para que `test-*` valide comportamiento, no solo sintaxis/compilación.
- Escaneo de seguridad de imágenes (p. ej. Trivy) antes del push.
- Despliegue por entornos (staging → production) con aprobación manual (`environment` protegido).
