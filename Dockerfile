ARG INSTALL_CRON=1
ARG PHP_EXTENSIONS="pdo pdo_mysql opcache gd apcu"
FROM thecodingmachine/php:7.4-v4-apache

# Change back Apache user and group to www-data
ENV APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data

USER root

# install jq since we need it for config.json generation in the entrypoint
RUN apt update && apt install -y \
    jq \
 && rm -rf /var/lib/apt/lists/*

# init composer before copying sources to make repeated dev builds faster
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/
COPY . /var/www/html/
COPY docker/entrypoint.sh /root/entrypoint.sh
RUN composer install --no-dev --no-progress --apcu-autoloader --no-ansi --no-interaction --no-cache

RUN mkdir /var/log/tg-bots/ && \
    chown -R www-data:www-data /var/www/html/ /var/log/tg-bots

ENV TEMPLATE_PHP_INI="production" \
    PHP_INI_EXTENSION="gd"
ENTRYPOINT [ "/root/entrypoint.sh" ]
CMD apache2-foreground
