# Media Storage Service (Object/Blob store autoalojado)

Servicio **independiente y especializado** en almacenar archivos multimedia. Es una solución de **Object/Blob storage autoalojada**, ejecutada por completo dentro de Docker sobre un **volumen persistente**. No usa Supabase, Azure Blob ni Amazon S3.

## Arquitectura del almacenamiento

```
Item Service ──HTTP (X-Internal-Token)──> Media Service ──> /var/blobstore (Docker volume: media-blob-data)
                                                 │
                                                 └──> PostgreSQL (recloset_media): metadatos y referencias
```

- **Binarios**: en el sistema de archivos del contenedor, bajo `MEDIA_STORAGE_ROOT=/var/blobstore`, respaldado por el volumen Docker `media-blob-data`.
- **Metadatos**: en la tabla `media_files` (id, item_id, mime, extensión, tamaño, path, checksum, timestamps).

El item-service **nunca** accede al volumen ni al sistema de archivos: toda operación multimedia pasa por la API del media-service.

## Cómo se almacenan los archivos

Estructura organizada por fecha + *sharding* por prefijo del UUID, para evitar directorios enormes y colisiones:

```
/var/blobstore/2026/07/a3/a3f1c2e8-....-9b21.jpg
                 │    │  │   └── {uuid}.{ext}
                 │    │  └────── shard = 2 primeros chars del uuid
                 │    └───────── mes
                 └────────────── año
```

## Cómo se generan los identificadores

- Cada archivo recibe un **UUID v4** (`Str::uuid()`), que es la **clave primaria** de `media_files` y el `media_id` que el item-service guarda como referencia.
- El UUID hace imposible la colisión de nombres y evita sobrescrituras: el fichero se llama `{uuid}.{ext}` y el movimiento es atómico (`move`).

## Cómo se asocian a las prendas

- En la subida, el item-service envía `item_id`; se guarda en `media_files.item_id`.
- El item-service guarda en su propia tabla `item_media` la referencia `{ media_id, url, mime, position }`.
- La URL pública devuelta es relativa al gateway: `/api/media/{uuid}/raw`. El navegador carga las imágenes por ahí.
- Una prenda admite **uno o varios** archivos (validado 1–8 en la creación).

## Cómo se eliminan

- Al borrar una prenda, el item-service llama `DELETE /api/media/by-item/{itemId}`.
- El media-service elimina cada blob del volumen y su fila de metadatos.
- La referencia en `item_media` se elimina en cascada al borrar la prenda.
- No quedan **archivos huérfanos** (el borrado por `item_id` cubre todos los del ítem) ni **referencias colgantes** (cascada en BD).

## Cómo se garantiza la persistencia

- El directorio `/var/blobstore` está montado en el volumen Docker **`media-blob-data`** (ver `docker-compose.yml`).
- Los volúmenes Docker sobreviven a: reinicios del contenedor, `docker compose restart`, eliminación/recreación del contenedor (`docker compose up --build`).
- Solo `docker compose down -v` (borrado explícito de volúmenes) elimina los datos.
- En Kubernetes, el equivalente es el `PersistentVolumeClaim` `media-blob-pvc`.

## Cómo se ejecuta el servicio

Forma parte de `docker-compose.yml` como `media-service` + `media-db`, con el volumen `media-blob-data:/var/blobstore`. Arranca solo con `docker compose up -d`.

## Cómo se protegen los archivos

- **Escritura y borrado**: solo con `X-Internal-Token` (secreto compartido servicio-a-servicio). El navegador no puede subir ni borrar directamente.
- **Lectura**: `GET /api/media/{id}/raw` es público (para mostrar imágenes), pero la URL contiene un UUID no adivinable.
- **Validación de entrada**: MIME real detectado por el servidor, extensión derivada del MIME (no se confía en la del cliente), tamaño máximo, nombre saneado.
- **Integridad**: se guarda el `sha256` (`checksum`) de cada archivo.

## Validaciones aplicadas

| Validación | Regla |
|------------|-------|
| Tipo MIME | `image/jpeg, image/png, image/webp, image/gif, video/mp4, video/webm` |
| Extensión | derivada del MIME detectado por el servidor |
| Tamaño máximo | `MEDIA_MAX_FILE_SIZE` (por defecto 50 MB) |
| Nombre | saneado (`[^\w.\- ]` → `_`), truncado a 180 chars |
| Contenido | `getMimeType()` de Symfony sobre el binario real |
