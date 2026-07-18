# ReCloset

Plataforma de microservicios para **publicar, explorar y gestionar prendas de vestir**, con **chat en tiempo real** entre personas interesadas en una prenda.

Stack obligatorio implementado: **Vue 3** (frontend) · **Laravel 11** (microservicios) · **Laravel Reverb** (tiempo real) · **PostgreSQL** (base de datos por servicio) · **Docker / Docker Compose** · **Kubernetes** (orquestación) · **Prometheus** (única herramienta de observabilidad).

---

## 1. Arquitectura en una imagen

```
                       ┌──────────────────┐
                       │  FRONTEND (Vue)  │  :5173
                       └────────┬─────────┘
                                │  HTTP / WebSocket
                                ▼
                       ┌──────────────────┐
                       │   API GATEWAY    │  :8080  (nginx)
                       └───┬────┬────┬────┬┘
             /api/auth │   /api/items │ /api/conversations │ /api/media
                       ▼        ▼     ▼        ▼            ▼   /app (WS)
              ┌────────────┐ ┌────────┐ ┌────────────┐ ┌──────────────┐ ┌────────┐
              │Auth Service│ │  Item  │ │Chat Service│ │Media Service │ │ Reverb │
              │  +Sanctum  │ │Service │ │ +Reverb    │ │ (blob store) │ │  (WS)  │
              └─────┬──────┘ └───┬────┘ └─────┬──────┘ └──────┬───────┘ └────────┘
                    ▼            ▼            ▼               ▼
               PostgreSQL   PostgreSQL   PostgreSQL      PostgreSQL   +   Docker Volume
                (auth)        (items)      (chat)          (media)        (/var/blobstore)
                                                                              ▲
                                                     Item Service ── HTTP ────┘ (nunca toca el volumen)
```

Cada microservicio es **independiente**, tiene su **propia base de datos PostgreSQL** y su **propio contenedor**. La comunicación entre servicios es **exclusivamente por HTTP/APIs** (nunca acceso directo a la BD de otro servicio).

Documentación detallada en [`docs/`](docs/):

| Documento | Contenido |
|-----------|-----------|
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Microservicios, responsabilidades, comunicación, diagramas |
| [PATTERNS.md](docs/PATTERNS.md) | 3 patrones de diseño (API Gateway, Database per Service, Circuit Breaker + Retry) |
| [API.md](docs/API.md) | Endpoints, parámetros, respuestas, errores, autenticación |
| [MEDIA_STORAGE.md](docs/MEDIA_STORAGE.md) | Object/Blob store autoalojado, persistencia, subida/consulta/eliminación |
| [DATABASE.md](docs/DATABASE.md) | Bases de datos, migraciones, relaciones, separación de datos |
| [DOCKER.md](docs/DOCKER.md) | Construcción, ejecución, logs, volúmenes, redes |
| [KUBERNETES.md](docs/KUBERNETES.md) | Despliegue, escalado, probes, secrets |
| [MONITORING.md](docs/MONITORING.md) | Prometheus: justificación, configuración, consultas |

---

## 2. Arranque rápido (Docker Compose)

Requisito único: **Docker Desktop** (incluye Docker Compose v2). No necesitas instalar PHP, Node ni PostgreSQL: todo corre en contenedores.

```bash
# 1) Copia las variables de entorno (único paso previo)
cp .env.example .env        # en Windows PowerShell:  copy .env.example .env

# 2) Levanta todo el stack (la primera vez tarda: descarga imágenes y compila)
docker compose up -d --build

# 3) Comprueba el estado (espera a que todos digan "healthy")
docker compose ps
```

Las claves de aplicación (`APP_KEY`) **se generan solas** dentro de cada contenedor; no hace falta `make keys`. Las migraciones y el catálogo inicial (categorías, tallas, colores) también se cargan automáticamente al arrancar.

Verifica que responde:

```bash
bash scripts/smoke-test.sh      # en Windows: usa Git Bash, o abre las URLs en el navegador
```

Servicios expuestos:

| URL | Servicio |
|-----|----------|
| http://localhost:5173 | Frontend (Vue) |
| http://localhost:8080 | API Gateway |
| http://localhost:9090 | Prometheus |

Las migraciones y el *seeding* del catálogo (categorías, tallas, colores) se ejecutan automáticamente al arrancar cada servicio (ver `docker/entrypoint.sh`).

### Comandos útiles

```bash
make logs      # logs de todos los servicios (JSON estructurado)
make ps        # estado de contenedores
make migrate   # re-ejecuta migraciones manualmente
make down      # detener y eliminar contenedores (los volúmenes persisten)
```

---

## 3. Flujo funcional cubierto

- **Registro / login / logout** con contraseñas *hasheadas* (bcrypt) y tokens Sanctum — RF-01, RF-02.
- **Autorización**: solo el propietario edita, cambia estado o elimina su prenda — RF-03.
- **Publicar prenda** con 1..N imágenes/videos; empieza como *Disponible* — RF-04, RF-05.
- **Catálogo público** con **filtros combinados** categoría + talla + color — RF-06, RF-07.
- **Gestión de armario**: ver, editar, cambiar estado, eliminar (con confirmación) — RF-08.
- **Resumen del armario**: total / disponibles / reservadas / vendidas — RF-09.
- **Chat por prenda** (sin auto-conversación) en **tiempo real con Reverb** — RF-10, RF-11.
- **Lista de conversaciones** ordenada por mensaje más reciente — RF-12.
- **Eliminar conversación** con sus mensajes — RF-13.
- **Notificaciones** por mensaje nuevo: creadas, almacenadas, contador de no leídas, marcar como leídas, y **push en tiempo real**.

---

## 4. Seguridad

- Contraseñas *hasheadas* (nunca en texto plano); política de fortaleza (8+, mayús/minús, número).
- Autenticación por token (Sanctum) e **introspección de token** entre servicios.
- Autorización a nivel de backend en cada endpoint privado.
- Validación **en frontend y backend**; validación de archivos (MIME real, extensión, tamaño, nombre).
- Secretos por variables de entorno / `Secret` de Kubernetes; nada de credenciales en el código.
- Errores manejados sin filtrar información sensible; logs sin contraseñas ni tokens.

Ver detalles en [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md#seguridad).
