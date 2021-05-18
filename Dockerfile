ARG PHP_VERSION

# See https://github.com/thecodingmachine/docker-images-php
FROM thecodingmachine/php:${PHP_VERSION}-v4-cli

RUN sudo apt-get update \
    && sudo apt-get install -y make \
    && sudo rm -rf /var/lib/apt/lists/*

ENV PATH="${PATH}:/usr/src/app/vendor/bin"
