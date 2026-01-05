#!/bin/bash

chown www-data:www-data /var/log/cron.log
chmod 664 /var/log/cron.log

echo "Starting cron..."
cron

echo "Starting php-fpm..."
exec php-fpm