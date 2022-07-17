FROM php:8.1-apache

# from https://stackoverflow.com/questions/39657058/installing-gd-in-docker/39658592#39658592
RUN apt-get update && \
    apt-get install -y \
        zlib1g-dev libpng-dev
RUN docker-php-ext-install gd

WORKDIR /var/www/html

# mount the root of your lichen-with-markdown directory to this volume
VOLUME /var/www/html

EXPOSE 80

RUN a2enmod headers && a2enmod rewrite && service apache2 restart
RUN mkdir -p /home/protected
RUN htpasswd -bc /home/protected/lichen.htpasswd yourusername yourpassword

RUN chown -R www-data:www-data .