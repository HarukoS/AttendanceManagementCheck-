FROM php:8.1-fpm

RUN apt-get update && apt-get install -y \
    cron \
    default-mysql-client \
    zlib1g-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_mysql

COPY docker/php/php.ini /usr/local/etc/php/

WORKDIR /var/www

COPY docker/crontab /etc/cron.d/crontab

RUN chmod 0644 /etc/cron.d/crontab \
    && crontab /etc/cron.d/crontab

CMD ["sh", "-c", "cron && php-fpm -F"]
