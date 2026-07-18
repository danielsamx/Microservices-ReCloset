# Referencia de API

Base pública (a través del gateway): `http://localhost:8080`

Autenticación de usuario: cabecera `Authorization: Bearer <token>` (token emitido por auth-service).
Autenticación de servicio (interna): cabecera `X-Internal-Token: <secreto>` — no accesible desde el navegador.

Todas las respuestas son JSON. Errores comunes: `401` no autenticado, `403` sin permiso, `404` no encontrado, `422` validación (`{ "message", "errors": { campo: [..] } }`).

---

## Auth Service — `/api/auth`

| Método | Ruta | Auth | Cuerpo | Respuesta |
|--------|------|------|--------|-----------|
| POST | `/register` | — | `name, email, password, password_confirmation` | `201 { user, token }` |
| POST | `/login` | — | `email, password` | `200 { user, token }` |
| POST | `/logout` | Bearer | — | `200 { message }` |
| GET | `/me` | Bearer | — | `200 { user }` |
| GET | `/verify` | Bearer | — | `200 { user:{id,name,email} }` (introspección) |
| GET | `/internal/users/{id}` | X-Internal-Token | — | `200 { user:{id,name} }` |

`password` debe tener 8+ caracteres, mayúsculas y minúsculas y al menos un número. `email` único.

---

## Item Service

| Método | Ruta | Auth | Notas |
|--------|------|------|-------|
| GET | `/api/meta` | — | Catálogos: `{ categories, sizes, colors }` |
| GET | `/api/catalog` | opcional | Lista pública de prendas **disponibles** de otros usuarios. Query: `category_id, size_id, color_id, search, per_page, page`. Paginado. |
| GET | `/api/catalog/{id}` | — | Detalle de una prenda. |
| GET | `/api/items/mine` | Bearer | Prendas del usuario (cualquier estado). |
| GET | `/api/wardrobe/summary` | Bearer | `{ total, available, reserved, sold }`. |
| POST | `/api/items` | Bearer | **multipart**: `name, description, category_id, size_id, color_id, price, files[]` (1–8, imágenes/mp4/webm ≤ 50MB). Crea prenda *available*. |
| PATCH | `/api/items/{id}` | Bearer (dueño) | Editar campos. |
| PATCH | `/api/items/{id}/status` | Bearer (dueño) | `{ status: available|reserved|sold }`. |
| DELETE | `/api/items/{id}` | Bearer (dueño) | Elimina prenda y sus archivos (vía media-service). |
| GET | `/api/items/internal/{id}` | X-Internal-Token | Datos mínimos para el chat. |

Filtros combinados (RF-07): se aplican simultáneamente en la misma query, p.ej. `/api/catalog?category_id=1&size_id=3&color_id=2`.

---

## Chat Service

| Método | Ruta | Auth | Notas |
|--------|------|------|-------|
| POST | `/api/broadcasting/auth` | Bearer | Firma la suscripción a canales privados de Echo. |
| GET | `/api/conversations` | Bearer | Conversaciones del usuario, ordenadas por mensaje más reciente, con `unread`. |
| POST | `/api/conversations` | Bearer | `{ item_id }`. Crea/recupera la conversación. `422` si intentas chatear contigo mismo. |
| GET | `/api/conversations/{id}` | Bearer (participante) | Mensajes; marca como leídos los entrantes. |
| DELETE | `/api/conversations/{id}` | Bearer (participante) | Elimina conversación + mensajes (RF-13). |
| POST | `/api/conversations/{id}/messages` | Bearer (participante) | `{ body }`. Guarda, difunde por Reverb y notifica al receptor. |
| GET | `/api/notifications` | Bearer | Notificaciones (100 recientes). |
| GET | `/api/notifications/unread-count` | Bearer | `{ count }`. |
| PATCH | `/api/notifications/{id}/read` | Bearer | Marca una como leída. |
| PATCH | `/api/notifications/read-all` | Bearer | Marca todas como leídas. |

**Canales en tiempo real (Reverb / protocolo Pusher):**
- `private-conversation.{id}` — evento `.message.sent`.
- `private-notifications.{userId}` — evento `.notification.created`.

---

## Media Service

| Método | Ruta | Auth | Notas |
|--------|------|------|-------|
| POST | `/api/media` | X-Internal-Token | **multipart** `file` + `item_id`. Guarda blob y metadatos. `201 { media:{id,url,mime,size,original_name} }`. |
| GET | `/api/media/{id}/raw` | — (público) | Sirve el binario (imagen/video) con caché. |
| GET | `/api/media/{id}/meta` | X-Internal-Token | Metadatos. |
| DELETE | `/api/media/{id}` | X-Internal-Token | Borra un archivo. |
| DELETE | `/api/media/by-item/{itemId}` | X-Internal-Token | Borra todos los archivos de una prenda. |

Validación de subida: MIME permitido (`image/jpeg,png,webp,gif`, `video/mp4,webm`), tamaño ≤ `MEDIA_MAX_FILE_SIZE`, nombre saneado, extensión derivada del MIME real.

---

## Operación (todos los servicios)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/health` | Estado + chequeo de BD (`200` ok / `503` degradado). |
| GET | `/metrics` | Métricas en formato Prometheus (up, requests, errores, latencia media). |
