# Bases de datos

**PostgreSQL 16**, una instancia por servicio (patrón *Database per Service*). Cada base corre en Docker con **volumen persistente**, variables de entorno y **migraciones** versionadas de Laravel.

## Instancias

| Servicio | Base | Volumen | Tablas |
|----------|------|---------|--------|
| auth | `recloset_auth` | `auth-db-data` | `users`, `password_reset_tokens`, `personal_access_tokens` |
| item | `recloset_items` | `item-db-data` | `categories`, `sizes`, `colors`, `items`, `item_media` |
| chat | `recloset_chat` | `chat-db-data` | `conversations`, `messages`, `notifications` |
| media | `recloset_media` | `media-db-data` | `media_files` |

## Relaciones internas (por base)

- **items**: `items` → `categories`/`sizes`/`colors` (FK); `item_media` → `items` (FK, `cascadeOnDelete`).
- **chat**: `messages` → `conversations` (FK, cascade); `conversations` con único `(item_id, interested_id)` (una conversación por comprador/prenda); `notifications` por `user_id`.
- **media**: `media_files` con PK `uuid`, `item_id` indexado.

## Referencias entre servicios (sin FK cruzadas)

Los IDs ajenos se guardan como enteros/strings **sin** clave foránea a otra base:
- `items.owner_id` ← id de usuario (auth).
- `item_media.media_id` ← uuid (media).
- `conversations.item_id` / `owner_id` / `interested_id` ← ids de item/usuarios.

Para evitar lecturas cruzadas frecuentes se **denormalizan** algunos campos: `items.owner_name`, `conversations.item_title/item_thumb/owner_name/interested_name`. Se rellenan por API en el momento de creación.

## Migraciones

Cada servicio tiene sus migraciones en `database/migrations/` y se ejecutan automáticamente al arrancar (`docker/entrypoint.sh` → `php artisan migrate --force`). El item-service además siembra catálogos (`db:seed`).

```bash
make migrate    # ejecuta migraciones en los 4 servicios
make fresh      # item-service: migrate:fresh --seed
```

## Integridad de datos

- Claves foráneas y `cascadeOnDelete` dentro de cada base.
- Restricciones `unique` (email, `(item_id, interested_id)`, `token`).
- Índices en columnas de filtro/orden (`status`, `owner_id`, `last_message_at`, `user_id`).
- Transacción en la creación de prenda + inserción de referencias multimedia.
