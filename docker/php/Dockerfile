FROM php:7.2-cli-alpine3.7

RUN apk update \
    && apk add --no-cache \
        zlib-dev \
        zip \
        openssl-dev \
    && docker-php-ext-install \
        bcmath \
        mbstring \
        zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

RUN apk add --no-cache --virtual .phpize_deps $PHPIZE_DEPS \
    && pecl install xdebug-2.6.1 mongodb \
    && docker-php-ext-enable xdebug mongodb \
    && rm -rf /usr/share/php7 \
    && apk del .phpize_deps

ENV PATH /var/app/bin:/var/app/vendor/bin:$PATH

WORKDIR /var/app