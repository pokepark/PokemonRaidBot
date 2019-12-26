#!/bin/bash

source /etc/apache2/envvars
cron
exec apache2 -D FOREGROUND
