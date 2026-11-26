# Base image: PHP 8.3 versi patch TERBARU di Debian 12 (bookworm).
# Sengaja tidak dikunci ke nomor patch (dulu 8.3.13) supaya setiap build ulang
# otomatis mendapat perbaikan keamanan PHP, Apache, dan OpenSSL terbaru.
# Di Coolify: lakukan build ulang TANPA cache agar base image terbaru ditarik.
FROM php:8.3-apache-bookworm

RUN apt-get update \
    # pasang semua pembaruan keamanan Debian yang terbit setelah base image dibuat
    && apt-get upgrade -y \
    && apt-get install -y libicu-dev libxml2-dev libzip-dev libpng-dev libonig-dev libjpeg62-turbo libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-install intl xml xmlwriter gettext mbstring zip mysqli pdo_mysql \
    && docker-php-ext-enable intl xml xmlwriter gettext mbstring zip mysqli pdo_mysql \
    && docker-php-ext-install -j$(nproc) iconv \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# Hardening: sembunyikan versi Apache/PHP di header dan halaman error (mis. 404)
RUN printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/zz-hardening.conf \
    && a2enconf zz-hardening \
    && printf 'expose_php = Off\n' > /usr/local/etc/php/conf.d/zz-hardening.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html/files
RUN chown -R www-data:www-data /var/www/html/images
RUN chown -R www-data:www-data /var/www/html/repository
RUN rm -rf /var/www/html/install

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

# Health check: Apache + PHP + koneksi database lewat /health.php (lihat file itu).
# - start-period 90 dtk: entrypoint.sh menunggu database siap dulu sebelum Apache jalan,
#   kegagalan selama masa ini tidak dihitung.
# - container ditandai "unhealthy" setelah 3 kali gagal berturut-turut (sekitar 1,5 menit).
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD curl -fsS -o /dev/null http://127.0.0.1/health.php || exit 1

ENTRYPOINT ["/entrypoint.sh"]

CMD ["apache2-foreground"]

