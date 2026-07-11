FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends cron tzdata \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY . /var/www/html

COPY docker/cron/vcs-notifications /etc/cron.d/vcs-notifications
RUN chmod 0644 /etc/cron.d/vcs-notifications

RUN a2enmod rewrite

EXPOSE 80
