FROM php:8.2.10-apache

RUN apt-get update \
  && apt-get install -y --no-install-recommends libpq-dev \
  && docker-php-ext-install mysqli pdo_pgsql pdo_mysql

# Enable Apache ldap auth module
RUN apt-get update -y --fix-missing && apt-get upgrade -y
RUN apt-get install -y libldb-dev libldap2-dev && docker-php-ext-install -j$(nproc) ldap

# # Enable mod_http2
RUN a2enmod http2 ssl

# # Update Apache configuration to enable HTTP/2
RUN echo "Protocols h2 http/1.1" >> /etc/apache2/apache2.conf

# # Restart Apache to apply the changes
RUN service apache2 restart