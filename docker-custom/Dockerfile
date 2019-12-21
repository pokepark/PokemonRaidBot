FROM ubuntu:bionic

ENV OS_LOCALE="de_DE.UTF-8"
RUN apt-get update && apt-get install -y locales && locale-gen ${OS_LOCALE}
ENV LANG=${OS_LOCALE} \
    LANGUAGE=${OS_LOCALE} \
    LC_ALL=${OS_LOCALE} \
    DEBIAN_FRONTEND=noninteractive

ENV APACHE_CONF_DIR=/etc/apache2 \
    PHP_CONF_DIR=/etc/php/7.3 \
    PHP_DATA_DIR=/var/lib/php \
	WWW_DIR=/var/www/html

COPY entrypoint.sh /sbin/entrypoint.sh

RUN	\
	BUILD_DEPS='software-properties-common' \
    && dpkg-reconfigure locales \
	&& apt-get install --no-install-recommends -y $BUILD_DEPS \
	&& add-apt-repository -y ppa:ondrej/php \
	&& add-apt-repository -y ppa:ondrej/apache2 \
	&& apt-get update \
    && apt-get install -y curl apache2 libapache2-mod-php7.3 php7.3-cli php7.3-readline php7.3-mbstring php7.3-zip php7.3-intl php7.3-xml php7.3-json php7.3-curl php7.3-gd php7.3-pgsql php7.3-mysql php-pear git cron \
    # Apache settings
    && cp /dev/null ${APACHE_CONF_DIR}/conf-available/other-vhosts-access-log.conf \
    && rm ${APACHE_CONF_DIR}/sites-enabled/000-default.conf ${APACHE_CONF_DIR}/sites-available/000-default.conf \
    && a2enmod rewrite php7.3 \
	# Install composer
	&& curl -sS https://getcomposer.org/installer | php -- --version=1.8.4 --install-dir=/usr/local/bin --filename=composer \
	# Cleaning
	&& apt-get purge -y --auto-remove $BUILD_DEPS \
	&& apt-get autoremove -y \
	&& rm -rf /var/lib/apt/lists/* \
	# Forward request and error logs to docker log collector
	&& ln -sf /dev/stdout /var/log/apache2/access.log \
	&& ln -sf /dev/stderr /var/log/apache2/error.log \
	&& chmod 755 /sbin/entrypoint.sh \
	&& chown www-data:www-data ${PHP_DATA_DIR} -Rf

# Download latest Raidbot
RUN git clone https://github.com/florianbecker/PokemonRaidBot.git
RUN git clone https://github.com/florianbecker/php.core.telegram.git

# Copy Files to Web-Folder to make it available
RUN cp -r ./PokemonRaidBot/. /var/www/html/
RUN rm -f -r PokemonRaidBot/
RUN cp -r ./php.core.telegram/. /var/www/html/core
RUN rm -f -r php.core.telegram/
RUN chown www-data:www-data ${WWW_DIR} -Rf

# Download Pokemon-Images
# Comment this line, if you don't want to download all images. -> Takes up to 10 min.
RUN php /var/www/html/getZeCharles.php

COPY ./apache2.conf ${APACHE_CONF_DIR}/apache2.conf
COPY ./app.conf ${APACHE_CONF_DIR}/sites-enabled/app.conf
COPY ./php.ini  ${PHP_CONF_DIR}/apache2/conf.d/custom.inis

WORKDIR ${WWW_DIR}

EXPOSE 80

#Activate Cronjob
COPY cronjob /etc/cron.d/cronjob
RUN chmod 0644 /etc/cron.d/cronjob
RUN crontab /etc/cron.d/cronjob


# By default, simply start apache.
CMD ["/sbin/entrypoint.sh"]
