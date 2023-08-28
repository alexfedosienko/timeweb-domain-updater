FROM composer:latest as vendor

WORKDIR /app

COPY /app/composer.json /app/composer.json
COPY /app/composer.lock /app/composer.lock

RUN composer install \
  --no-interaction \
  --no-plugins \
  --no-scripts \
  --no-dev \
  --prefer-dist

RUN composer dump-autoload --optimize

FROM php:8.2-cli-alpine3.18

WORKDIR /app
RUN apk add mc htop nano
RUN rm -rf /var/cache/apk/*

COPY --from=vendor /app/vendor /app/vendor
COPY /app/src /app/src
# RUN touch .env
RUN mkdir /app/data
#COPY /app/data/config.json /app/data/config.json

COPY ./default.cron /etc/crontabs/root

CMD ["crond", "-f", "-d", "8"]
