# ==============================================================================
# SI-DRIPP — Dockerfile untuk Render Web Service (PHP 8.2 Apache + PostgreSQL)
# Proyek: Sistem Informasi Pendataan Stok & Transaksi MKP Store Malang
# ==============================================================================
FROM php:8.2-apache

# 1. Install dependensi sistem untuk PostgreSQL, ZIP (Export Excel), GD, & SSL CA
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        ca-certificates \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pgsql \
        pdo_pgsql \
        zip \
        gd \
    && rm -rf /var/lib/apt/lists/*

# 2. Aktifkan Apache mod_rewrite & izinkan .htaccess (AllowOverride All)
RUN a2enmod rewrite \
    && printf "ServerName localhost\n" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && printf "<Directory /var/www/html>\n\tOptions -Indexes +FollowSymLinks\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n" > /etc/apache2/conf-available/sidripp-dir.conf \
    && a2enconf sidripp-dir

# 3. Konfigurasi PHP produksi (WIB, EGPCS untuk Render Env Vars, & batas upload foto)
RUN { \
        echo 'variables_order = "EGPCS"'; \
        echo 'date.timezone = "Asia/Jakarta"'; \
        echo 'upload_max_filesize = 10M'; \
        echo 'post_max_size = 12M'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /proc/self/fd/2'; \
    } > /usr/local/etc/php/conf.d/sidripp.ini

# 4. Salin seluruh kode proyek ke DocumentRoot Apache & atur hak akses folder
WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/assets/img/products \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/assets/img/products

# 5. Konfigurasi port dinamis ${PORT} untuk Render Web Service (default 8000)
ENV PORT=8000
EXPOSE 8000

CMD ["sh", "-c", "PORT=${PORT:-8000}; sed -i \"s/^Listen .*/Listen ${PORT}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:[0-9]*>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
