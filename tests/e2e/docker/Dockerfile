# Aplicação como será publicada: Apache + PHP em Linux, com o código copiado para a imagem.
FROM composer:2 AS dependencias
WORKDIR /app
COPY composer.json composer.lock ./
COPY backend ./backend
RUN composer install --no-dev --no-interaction --ignore-platform-reqs --optimize-autoloader

FROM php:8.2-apache
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers deflate \
    && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && echo 'date.timezone = Europe/Berlin' > /usr/local/etc/php/conf.d/fuso.ini

# Mesmo caminho do XAMPP: /sistema_ca_jesus, com o .htaccess da raiz valendo.
WORKDIR /var/www/html/sistema_ca_jesus
COPY . .
COPY --from=dependencias /app/vendor ./vendor
COPY tests/e2e/docker/config.php ./backend/config/config.php
RUN mkdir -p backend/storage/logs && chown -R www-data:www-data backend/storage
