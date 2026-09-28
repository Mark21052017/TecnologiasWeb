FROM php:8.5-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod headers

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

COPY docker/apache/tecnologiasweb.conf /etc/apache2/conf-available/tecnologiasweb.conf
RUN a2enconf tecnologiasweb

WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html
