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
    && chmod -R 775 bootstrap/cache storage/logs

RUN composer install --no-dev --optimize-autoloader

RUN a2enmod rewrite

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

ENV PORT 8080

RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf

RUN cat > /etc/apache2/sites-available/000-default.conf <<'EOF'
<VirtualHost *:8080>
    ServerName localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>

    <Directory /var/www/html>
        Options -Indexes
        AllowOverride None
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

CMD ["apache2-foreground"]
