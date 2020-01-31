FROM ubuntu:bionic

ENV OS_LOCALE="de_DE.UTF-8"
RUN apt-get update && apt-get install -y locales && locale-gen ${OS_LOCALE}
ENV LANG=${OS_LOCALE} \
    LANGUAGE=${OS_LOCALE} \
    LC_ALL=${OS_LOCALE} \
    DEBIAN_FRONTEND=noninteractive

ENV APACHE_CONF_DIR=/etc/apache2 \
    PHP_CONF_DIR=/etc/php/7.4 \
    PHP_DATA_DIR=/var/lib/php \
    WWW_DIR=/var/www/html/ \
    PHP_VER=php7.4

COPY /docker/entrypoint.sh /sbin/entrypoint.sh

RUN	\
	BUILD_DEPS='software-properties-common' \
  && dpkg-reconfigure locales \
  && apt-get update \
	&& apt-get install --no-install-recommends -y $BUILD_DEPS \
	&& add-apt-repository -y ppa:ondrej/php \
	&& add-apt-repository -y ppa:ondrej/apache2 \
	&& apt-get update \
  && apt-get upgrade -y \
  && apt-get install -y curl apache2 libapache2-mod-${PHP_VER} ${PHP_VER}-cli ${PHP_VER}-readline ${PHP_VER}-mbstring ${PHP_VER}-zip ${PHP_VER}-intl ${PHP_VER}-xml ${PHP_VER}-json ${PHP_VER}-curl ${PHP_VER}-gd ${PHP_VER}-pgsql ${PHP_VER}-mysql php-pear git cron \
  # Apache settings
  && cp /dev/null ${APACHE_CONF_DIR}/conf-available/other-vhosts-access-log.conf \
  && rm ${APACHE_CONF_DIR}/sites-enabled/000-default.conf ${APACHE_CONF_DIR}/sites-available/000-default.conf \
  && a2enmod rewrite ${PHP_VER} \
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
	&& chown -Rf www-data:www-data ${PHP_DATA_DIR}

# Download latest Raidbot
RUN \
  git clone https://github.com/florianbecker/PokemonRaidBot.git \
  && git clone https://github.com/florianbecker/php.core.telegram.git \
  # Download Pokemon-Images
  && php PokemonRaidBot/getZeCharles.php \
  # Copy Files to Web-Folder to make it available
  && cp -r PokemonRaidBot/ ${WWW_DIR} \
  && rm -f -r /PokemonRaidBot/ \
  && cp -r php.core.telegram/ ${WWW_DIR}core \
  && rm -f -r php.core.telegram/ \
  && chown -Rf www-data:www-data ${WWW_DIR}


COPY /docker/apache2.conf ${APACHE_CONF_DIR}/apache2.conf
COPY /docker/app.conf ${APACHE_CONF_DIR}/sites-enabled/app.conf
COPY /docker/php.ini  ${PHP_CONF_DIR}/apache2/conf.d/custom.inis

WORKDIR ${WWW_DIR}PokemonRaidBot/

EXPOSE 80

ADD /docker/cronjob /etc/cron.d/cronjob

RUN \
  #Activate Cronjob
  chmod 0644 /etc/cron.d/cronjob \
  && crontab /etc/cron.d/cronjob \
  && touch /var/log/cron.log

# By default, simply start apache.
CMD ["/sbin/entrypoint.sh"]
