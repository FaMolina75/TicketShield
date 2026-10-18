# ==========================================
# ETAPA 1: COMPILAR REACT CON VITE
# ==========================================

FROM node:20-alpine AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY postcss.config.js ./
COPY tailwind.config.js ./

RUN npm run build


# ==========================================
# ETAPA 2: LARAVEL + APACHE
# ==========================================

FROM php:8.2-apache

WORKDIR /var/www/html

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        intl \
        bcmath \
        exif \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar Apache
RUN a2enmod rewrite headers

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
    -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf

# Copiar proyecto
COPY . .

# Copiar archivos compilados de React
COPY --from=frontend /app/public/build ./public/build

# Instalar dependencias PHP
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Permisos de Laravel
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]