FROM php:8.2-apache

WORKDIR /var/www/html

COPY . /var/www/html/

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p bootstrap/cache storage/logs \
    && chmod -R 775 bootstrap/cache storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage/logs

RUN composer install --no-dev --optimize-autoloader

RUN a2enmod rewrite

RUN chown -R www-data:www-data /var/www/html

RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/laravel.conf && \
    a2enconf laravel

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf

EXPOSE 10000

CMD ["apache2-foreground"]
