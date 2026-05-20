FROM php:8.3-apache

RUN docker-php-ext-install pdo pdo_sqlite

COPY . /var/www/html/

RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && chown -R www-data:www-data /var/www/html/storage

EXPOSE 80
