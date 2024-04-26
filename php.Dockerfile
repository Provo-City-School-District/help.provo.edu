FROM php:8.3-apache

# php adjustments.
COPY config/customphp.ini /usr/local/etc/php/conf.d/

# install dependencies
RUN apt-get update \
  && apt-get install -y --no-install-recommends libc-client-dev libkrb5-dev libpq-dev libzip-dev zip unzip git wget libpng-dev libjpeg-dev zlib1g-dev cron \
  && docker-php-ext-install mysqli pdo_pgsql pdo_mysql zip

RUN docker-php-ext-configure gd --with-jpeg=/usr
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-install gd imap

# setup cron
COPY crontab /etc/cron.d/cron-job
RUN chmod 0644 /etc/cron.d/cron-job
RUN /usr/bin/crontab /etc/cron.d/cron-job


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

# # Set ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable mod_remoteip / Set RemoteIPInternalProxy
RUN a2enmod remoteip
RUN echo "RemoteIPHeader X-Forwarded-For"  >> /etc/apache2/apache2.conf
RUN echo "RemoteIPInternalProxy 158.91.1.103/24" >> /etc/apache2/apache2.conf

# Append ErrorDocument directives to Apache configuration
RUN echo "ErrorDocument 404 /errors/404.html" >> /etc/apache2/apache2.conf \
  && echo "ErrorDocument 403 /errors/404.html" >> /etc/apache2/apache2.conf

# Set ServerTokens directive to Prod
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf

# # Restart Apache to apply the changes
RUN service apache2 restart

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy in Composer config
COPY /src/composer.json /var/www/html/
COPY /src/composer.lock /var/www/html/

COPY /src/public/boot.php /var/www/html/public/boot.php

# since .env variables aren't available by default to CLI scripts, we need to copy the .env file to the root directory so it can be loaded for them
COPY .env /root/.env

# copy cronjobs
COPY run_ticket_alerts.sh /root/run_ticket_alerts.sh
RUN chmod +x /root/run_ticket_alerts.sh

COPY run_email_check.sh /root/run_email_check.sh
RUN chmod +x /root/run_email_check.sh

COPY run_close_resolved.sh /root/run_close_resolved.sh
RUN chmod +x /root/run_close_resolved.sh

COPY run_client_response.sh /root/run_client_response.sh
RUN chmod +x /root/run_client_response.sh

COPY run_active_ticket_clear.sh /root/run_active_ticket_clear.sh
RUN chmod +x /root/run_active_ticket_clear.sh


# Change document root folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create the uploads directory and set permissions
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads && chmod -R 775 /var/www/html/uploads
RUN mkdir -p /var/php/sessions && chown -R www-data:www-data /var/php/sessions && chmod -R 770 /var/www/html/uploads
RUN mkdir -p /var/www/html/twig-cache && chown -R www-data:www-data /var/www/html/twig-cache

RUN chown -R www-data:www-data /var/www/html/

CMD service cron start && chown -R www-data:www-data /var/www/html/uploads && chown -R www-data:www-data /var/www/html/vendor && chmod -R 550 /var/www/html/scripts && chown -R www-data:www-data /var/php/sessions && composer install --no-interaction --no-ansi --no-scripts --no-progress --prefer-dist && docker-php-entrypoint apache2-foreground