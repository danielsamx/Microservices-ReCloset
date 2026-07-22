# Diseño — Emails (Resend) + Recuperación de contraseña + 2FA por OTP

Fecha: 2026-07-21
Servicio afectado: `services/auth-service` (Laravel 11 + Sanctum) y `frontend` (Vue 3).

## Objetivo

Añadir al `auth-service`:
1. Envío de correos vía **Resend** (con fallback a `log` para desarrollo sin cuenta).
2. **Recuperación de contraseña** (forgot / reset).
3. **Verificación de email** al registrarse.
4. **Email de bienvenida**.
5. **Doble factor (2FA) por OTP de email**, opt-in desde el perfil.

No se modifican otros microservicios. El gateway ya proxya `/api/auth/*`. El entrypoint
auto-ejecuta `php artisan migrate --force` al arrancar.

## Decisiones

- **2FA:** OTP de 6 dígitos por email en cada login (si está activo).
- **Activación 2FA:** opt-in por usuario desde el perfil.
- **Emails:** los cuatro (reset, OTP, verificación, bienvenida).
- **Resend:** sin cuenta todavía → `MAIL_MAILER=log` por defecto. Al añadir `RESEND_API_KEY`
  y `MAIL_MAILER=resend`, envía real sin cambios de código.

## Backend

### Dependencia
- `resend/resend-laravel` — transporte de correo Resend.

### Migraciones (nuevas)
- `add_two_factor_to_users`: `users.two_factor_enabled` boolean default false.
- `create_two_factor_challenges_table`: `id` (uuid), `user_id`, `purpose` (`login`|`enable`),
  `token` (random, único — devuelto al cliente), `code_hash`, `expires_at`, `attempts`
  (int default 0), `created_at`.
- `create_email_verification_tokens_table`: `email` (primary), `token`, `created_at`.

`password_reset_tokens` ya existe.

### Configuración
- `config/mail.php`: añadir mailer `resend` (`'transport' => 'resend'`). Fallback `log`.
- Variables de entorno: `MAIL_MAILER` (default `log`), `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`,
  `RESEND_API_KEY`, `FRONTEND_URL`, `APP_URL`.

### Mailables (HTML branded, verde Eco Fresh)
- `WelcomeMail`, `VerifyEmailMail` (enlace), `ResetPasswordMail` (enlace), `TwoFactorCodeMail` (código).
- Plantilla base compartida con logo/cabecera/pie.

### Endpoints (`/api/auth/...`)
Públicos:
- `POST /password/forgot` `{email}` → 200 genérico; si existe, envía enlace
  `FRONTEND_URL/reset-password?token=..&email=..`.
- `POST /password/reset` `{email, token, password, password_confirmation}`.
- `POST /email/verify` `{email, token}`.
- `POST /2fa/verify` `{challenge, code}` → token + user (completa login).

Autenticados (`auth:sanctum`):
- `POST /email/resend`.
- `POST /2fa/enable` → envía OTP `purpose=enable`, devuelve `challenge`.
- `POST /2fa/confirm` `{challenge, code}` → `two_factor_enabled=true`.
- `POST /2fa/disable` `{password}`.

Modificados:
- `login`: si `two_factor_enabled` → genera challenge `purpose=login`, envía OTP, responde
  `{requires_2fa: true, challenge}` (sin token). Si no, igual que hoy.
- `register`: crea usuario, envía bienvenida + verificación, devuelve token + user.
- `me`/payload de usuario: incluir `two_factor_enabled` y `email_verified` (bool).

### Reglas de seguridad
- Códigos y tokens **hasheados** en BD (`Hash::make` / `hash('sha256', ...)`).
- OTP: 6 dígitos, expira 10 min, máx. 5 intentos por challenge.
- Enlaces reset/verify: token aleatorio 64 hex, expira 60 min.
- Rate limiting (`throttle`) en `login`, `password/forgot`, `2fa/verify`, `email/resend`.
- Mensajes genéricos anti-enumeración en `password/forgot`.
- `2fa/disable` requiere contraseña actual.

## Frontend

### Rutas nuevas (público)
- `/forgot-password` → `ForgotPassword.vue`
- `/reset-password` → `ResetPassword.vue` (lee `token`, `email` de query)
- `/verify-email` → `VerifyEmail.vue` (lee `token`, `email` de query)

### Componentes
- `OtpInput.vue`: 6 casillas, autofocus/paste, estilo Eco Fresh + dark mode.

### Cambios
- `Login.vue`: enlace "¿Olvidaste tu contraseña?" y paso OTP cuando `requires_2fa`.
- `Register.vue`: aviso "verifica tu correo" tras registro.
- `Profile.vue`: sección **Seguridad** — toggle 2FA (activar→confirmar OTP en modal;
  desactivar→contraseña) y estado de verificación de email + reenviar.
- `store/auth.js`: acciones `forgotPassword`, `resetPassword`, `verifyEmail`,
  `resendVerification`, `enable2fa`, `confirm2fa`, `disable2fa`; `login` maneja `requires_2fa`.
- `router/index.js`: registrar las 3 rutas públicas nuevas.

## Infra
- `docker-compose.yml` (auth-service `environment`): `MAIL_MAILER`, `MAIL_FROM_ADDRESS`,
  `MAIL_FROM_NAME`, `RESEND_API_KEY`, `FRONTEND_URL`, `APP_URL`.
- `.env.example`: mismas variables documentadas.

## Verificación
- Tests backend (PHPUnit) para: login con 2FA (challenge → verify), password reset,
  email verify, enable/confirm/disable 2FA.
- Prueba manual del stack leyendo códigos/enlaces del log de `auth-service`
  (mientras no haya Resend).

## Fuera de alcance
- SMS/TOTP. Verificación obligatoria de email para publicar (se deja como opción futura).
