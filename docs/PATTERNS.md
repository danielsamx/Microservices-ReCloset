# Patrones de diseño

Se implementan **3 patrones**, cada uno con valor real en la arquitectura (no decorativos).

---

## 1. API Gateway

**Problema que resuelve.** Con múltiples servicios, el frontend no debería conocer la topología interna (hosts/puertos), ni repetir CORS, límites de tamaño o el enrutado en cada uno. También se necesita un único origen para el navegador y para el WebSocket.

**Aplicación.** Un contenedor **nginx** (`gateway/nginx.conf`) es el único punto de entrada público (`:8080`). Enruta por prefijo de path hacia el servicio correcto y hace *proxy* del WebSocket de Reverb en `/app`.

**Microservicios involucrados.** Todos (auth, item, chat, media, reverb) detrás del gateway; el frontend solo habla con el gateway.

**Flujo.**
```
/api/auth/*          -> auth-service:8000
/api/items|catalog|meta|wardrobe -> item-service:8000
/api/conversations|messages|notifications|broadcasting/auth -> chat-service:8000
/api/media/*         -> media-service:8000
/app/* (WebSocket)   -> reverb:8080
```

**Ventajas.** Un solo origen (simplifica CORS y el cliente); ocultamiento de la topología; punto único para límites de subida, cabeceras y, a futuro, rate-limiting/TLS.

**Limitaciones.** Punto único de fallo si no se replica (en K8s corre con `replicas: 2`); añade un salto de red; el enrutado por path debe mantenerse sincronizado con las rutas de cada servicio.

---

## 2. Database per Service

**Problema que resuelve.** Una base de datos compartida acopla los servicios (un cambio de esquema rompe a varios) e impide escalar o desplegar de forma independiente. Viola la autonomía de los microservicios.

**Aplicación.** Cada servicio tiene su **propia instancia PostgreSQL** y su propio esquema/migraciones. Ningún servicio accede a las tablas de otro; si necesita datos ajenos, los pide por API.

**Microservicios involucrados.**
- auth-service → `recloset_auth` (users, personal_access_tokens)
- item-service → `recloset_items` (items, item_media, categories, sizes, colors)
- chat-service → `recloset_chat` (conversations, messages, notifications)
- media-service → `recloset_media` (media_files)

**Flujo.** Ej.: el chat necesita el título/dueño de una prenda → **no** consulta `recloset_items`, sino `GET /api/items/internal/{id}` en item-service. Los IDs de usuario/prenda se guardan como referencias sueltas (sin *foreign keys* cruzadas entre bases).

**Ventajas.** Autonomía de esquema y despliegue; aislamiento de fallos; escalado independiente de cada base; límites de responsabilidad claros.

**Limitaciones.** No hay *joins* ni transacciones ACID entre servicios; la consistencia entre servicios es *eventual* y hay duplicación controlada (ej.: `owner_name` y `item_title` se copian en la conversación para no depender del otro servicio en cada lectura).

---

## 3. Circuit Breaker + Retry

**Problema que resuelve.** Las llamadas entre servicios pueden fallar por causas transitorias (arranque, latencia) o por caída sostenida de una dependencia. Reintentar sin control agrava la saturación; no reintentar rompe por *glitches* momentáneos.

**Aplicación.** El cliente de introspección de tokens (`App\Support\AuthClient`) y los clientes `MediaClient`/`ItemClient` implementan:
- **Retry** con *backoff* incremental (hasta 3 intentos, 0.2s→0.4s) para fallos transitorios, **sin** reintentar respuestas definitivas (401 token inválido, 422 validación).
- **Circuit Breaker**: tras 5 fallos consecutivos, el circuito se **abre** 30s y las llamadas fallan rápido (sin golpear la dependencia), evitando el efecto cascada. Se **cierra** al primer éxito.

**Microservicios involucrados.** item-service y chat-service (consumidores) frente a auth-service, media-service e item-service (dependencias).

**Flujo.**
```
verify(token):
  si circuito ABIERTO -> devuelve null inmediatamente (fail-fast)
  intento 1..3:
     GET /api/auth/verify
     200 -> cache 30s + éxito (cierra circuito)
     401 -> token inválido (no reintenta)
     error/5xx -> espera backoff y reintenta
  agota intentos -> registra fallo (puede abrir el circuito)
```

**Ventajas.** Resiliencia ante fallos transitorios; protección de dependencias saturadas; menor latencia al fallar rápido; caché reduce carga de introspección.

**Limitaciones.** Estado del *breaker* por instancia (con varias réplicas, el estado no es global — bastaría un store compartido como Redis para unificarlo); si se calibra mal, puede abrir el circuito antes de tiempo.

---

> **Nota sobre Event-Driven.** El tiempo real de Reverb es *event-driven* en el transporte (eventos `MessageSent`/`NotificationCreated`), pero el patrón arquitectónico documentado como resiliencia inter-servicio es Circuit Breaker + Retry, que aporta más valor en esta topología síncrona.
