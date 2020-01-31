ARG INSTALL_CRON=1
ARG PHP_EXTENSIONS="mysqli pdo pdo_mysql opcache gd"
FROM thecodingmachine/php:7.4-v3-apache

# Change back Apache user and group to www-data
ENV APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data

USER root

WORKDIR /var/www/html/
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/

