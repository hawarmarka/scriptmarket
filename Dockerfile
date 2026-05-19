FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mysqli gd zip \
    && a2enmod rewrite headers expires deflate \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && printf '%s\n' \
        'Alias /admin /var/www/html/admin' \
        '<Directory /var/www/html/admin>' \
        '    Options Indexes FollowSymLinks' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        > /etc/apache2/conf-available/scriptmarket-admin.conf \
    && a2enconf scriptmarket-admin \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && if [ -d "/var/www/html/public/uploads" ]; then chmod -R 775 /var/www/html/public/uploads; fi \
    && if [ -d "/var/www/html/uploads" ]; then chmod -R 775 /var/www/html/uploads; fi \
    && if [ -d "/var/www/html/storage" ]; then chmod -R 775 /var/www/html/storage; fi

EXPOSE 80

CMD ["apache2-foreground"]
