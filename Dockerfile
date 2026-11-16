FROM php:8.3.13-apache

RUN apt-get update \
    && apt-get install -y libicu-dev libxml2-dev libzip-dev libpng-dev libonig-dev libjpeg62-turbo libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-install intl xml xmlwriter gettext mbstring zip mysqli pdo_mysql \
    && docker-php-ext-enable intl xml xmlwriter gettext mbstring zip mysqli pdo_mysql \
    && docker-php-ext-install -j$(nproc) iconv \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd

RUN a2enmod rewrite

# Hardening: sembunyikan versi Apache/PHP di header dan halaman error (mis. 404)
RUN printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/zz-hardening.conf \
    && a2enconf zz-hardening \
    && printf 'expose_php = Off\n' > /usr/local/etc/php/conf.d/zz-hardening.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install

RUN chown -R www-data:www-data /var/www/html/files
RUN chown -R www-data:www-data /var/www/html/images
RUN chown -R www-data:www-data /var/www/html/repository
RUN rm -rf /var/www/html/install

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]

CMD ["apache2-foreground"]

