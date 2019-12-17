FROM php:7.4-apache

# Install cron + git + wget + zip + unzip
RUN apt-get update && apt-get -y install -qq cron git wget zip unzip

# Download latest Raidbot
RUN wget https://github.com/florianbecker/PokemonRaidBot/archive/master.zip

# unzip Raidbot files
RUN unzip master.zip

# Copy Files to Web-Folder to make it available
RUN cp -r ./PokemonRaidBot-master/. /var/www/html/

# Delete Download
RUN rm -f master.zip

# Download latest telegram Core
RUN wget https://github.com/florianbecker/php.core.telegram/archive/master.zip

# unzip Telegram Core
RUN unzip master.zip

# Copy core to Web-Folder
RUN cp -r ./php.core.telegram-master/. /var/www/html/core/

# Download Pokemon-Images
# Comment this line, if you don't want to download all images. -> Takes up to 10 min.
RUN php /var/www/html/getZeCharles.php

# change rights of Web-Folder
RUN chown -R www-data:www-data /var/www/

# Moving cronjob to Docker
COPY ./cronjob /etc/cron.d/cronjob

# Making it executable
RUN chmod 0644 /etc/cron.d/cronjob

# Starting Cronjob-Task
RUN crontab /etc/cron.d/cronjob
CMD ["cron", "-f"]

# Removing old downloaded files
RUN rm -f -r master.zip PokemonRaidBot-master/ php.core.telegram-master/
