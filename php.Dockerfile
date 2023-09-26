FROM php:8.2.10-apache

# php adjustments.
COPY config/customphp.ini /usr/local/etc/php/conf.d/

RUN apt-get update \
  && apt-get install -y --no-install-recommends libpq-dev zip unzip git wget \
  && docker-php-ext-install mysqli pdo_pgsql pdo_mysql

# Enable Apache ldap auth module
RUN apt-get update -y --fix-missing && apt-get upgrade -y
RUN apt-get install -y libldb-dev libldap2-dev && docker-php-ext-install -j$(nproc) ldap

# COPY ckroot.crt /usr/local/share/ca-certificates/ckroot.crt
RUN wget -P /usr/local/share/ca-certificates/ "https://ckr01.provo.edu/ckroot/ckroot.crt"
RUN chmod 644 /usr/local/share/ca-certificates/ckroot.crt && update-ca-certificates

# # Enable mod_http2
RUN a2enmod http2 ssl

# # Update Apache configuration to enable HTTP/2
RUN echo "Protocols h2 http/1.1" >> /etc/apache2/apache2.conf

# # Restart Apache to apply the changes
RUN service apache2 restart

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy in Composer config
COPY /src/composer.json /var/www/html/
COPY /src/composer.lock /var/www/html/

# Install Composer packages
RUN composer install --no-interaction --no-ansi --no-scripts --no-progress --prefer-dist
