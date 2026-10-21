# Despliegue seguro de TicketShield

## Antes de subir
1. `cp .env.production.example .env` y llénalo. `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`.
2. `php artisan key:generate`
3. `composer install --no-dev --optimize-autoloader` y `npm ci && npm run build`
4. `php artisan migrate --force` (NO ejecutes `db:seed` en producción: crea usuarios con contraseña "password"; ahora está bloqueado salvo `ALLOW_DEMO_SEED=true`).
5. `php artisan storage:link && php artisan config:cache && php artisan route:cache`
6. Crea tu admin real con `php artisan tinker` (rol `super-admin`) y contraseña fuerte.

## En la nube (lo que más pesa contra ataques)
- HTTPS obligatorio + redirección 80→443 (certificado del proveedor / Let's Encrypt).
- Raíz web = carpeta `public/`. Que `.env`, `storage/` y `vendor/` NUNCA sean accesibles por URL.
- Firewall/Security Group: solo 80/443 abiertos al público; MySQL (3306) y SSH solo desde tu IP / red privada.
- WAF + protección DDoS del proveedor (Cloudflare gratis, AWS WAF, etc.) delante de la app: aquí se frena el tráfico volumétrico que PHP no puede.
- Base de datos con usuario de permisos mínimos (no root), backups automáticos.
- Si hay más de una instancia: CACHE_DRIVER=redis para que los límites de peticiones (rate limit) se compartan.

## Qué ya quedó protegido en el código
- Límite de peticiones: login/registro/recuperación (10/min por IP), pago (5/min), contacto (3/min), global (120/min), validación de QR (30/min).
- Pago: transacción real, bloqueo de filas contra sobreventa concurrente, validación de cantidades y límites mín/máx, sin `dd()` (ya no filtra errores internos).
- Cabeceras de seguridad (CSP, HSTS, X-Frame-Options, nosniff, etc.), CORS cerrado a tu dominio, TrustHosts activo.
- Códigos de orden impredecibles (antes `mt_rand` de 1–1000, adivinables).
- Contacto: validado y sin `sleep(2)` (era un vector de agotamiento de workers).

## Pendientes conocidos (no los toqué por ser funcionalidad, no seguridad)
- El pago es **simulado** (Stripe está comentado): ninguna compra cobra dinero real.
- `ProfileController::cancel_order` usa `App\Helpers\Checkout` y `$user->payments`, que no existen: cancelar/reembolsar falla.
- `OrderService::createOrder` asigna `user_id` al organizador, no al comprador.
- Sin verificación de email forzada para registrarse (la ruta de pago sí exige `verified`; configura el correo).

## Subir a Railway (Dockerfile incluido)
1. Sube el proyecto a un repo de GitHub (sin `.env`).
2. railway.app → New Project → Deploy from GitHub → elige el repo (detecta el Dockerfile).
3. En el mismo proyecto: New → Database → MySQL.
4. En el servicio web → Variables: APP_KEY (`php artisan key:generate --show`), APP_ENV=production, APP_DEBUG=false,
   APP_URL=https://tu-app.up.railway.app, SESSION_SECURE_COOKIE=true, SESSION_DRIVER=file,
   DB_HOST=${{MySQL.MYSQLHOST}} DB_PORT=${{MySQL.MYSQLPORT}} DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}} DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
5. Settings → Networking → Generate Domain.
6. Para datos de demostración (solo para la clase): ALLOW_DEMO_SEED=true y ejecuta `php artisan db:seed --force` desde la consola del servicio; luego quita la variable.
