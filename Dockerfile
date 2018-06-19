FROM php:7

RUN apt-get update && apt-get -y upgrade && \
  apt-get install -y zlib1g-dev git p7zip-full
RUN docker-php-ext-install zip mbstring
RUN curl -sS https://getcomposer.org/installer |php -- --install-dir=/usr/local/bin --filename=composer
