FROM php:8.5-apache

RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork headers \
    && docker-php-ext-install pdo_mysql

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

COPY docker/apache/tecnologiasweb.conf /etc/apache2/conf-available/tecnologiasweb.conf
RUN a2enconf tecnologiasweb

WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

CMD ["sh", "-c", "if [ -n \"$RAILWAY_SERVICE_ID\" ]; then mkdir -p /var/www/html/storage/profile-images /var/www/html/storage/mg-academic-imports /var/www/html/storage/mg-reports /var/www/html/storage/sessions && chown -R www-data:www-data /var/www/html/storage; fi; rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf; a2enmod mpm_prefork; exec apache2-foreground"]
