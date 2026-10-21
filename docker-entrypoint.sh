#!/bin/sh

# Render y Railway inyectan la variable $PORT (por defecto 10000 o dinámica)
PORT="${PORT:-80}"

if [ "$PORT" != "80" ]; then
    echo "Configurando Apache para escuchar en el puerto ${PORT}..."
    sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -ri "s/:[0-9]+>/:${PORT}>/" /etc/apache2/sites-available/*.conf
fi

# Crear enlace simbólico de almacenamiento
php artisan storage:link 2>/dev/null || true

# Ejecutar migraciones de forma segura sin abortar el arranque de Apache si la BD tarda
php artisan migrate --force 2>/dev/null || echo "Aviso: Migraciones no ejecutadas o base de datos no conectada aún."

# Limpiar cachés para cargar las variables de entorno de Render
php artisan view:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

echo "Iniciando Apache en el puerto ${PORT}..."
exec apache2-foreground
