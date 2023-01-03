ARG PHP_VERSION

# See https://github.com/thecodingmachine/docker-images-php
FROM thecodingmachine/php:${PHP_VERSION}-v4-cli

RUN sudo apt-get update \
    && sudo apt-get install -y make \
    && sudo rm -rf /var/lib/apt/lists/*

# Install symfony/flex for SYMFONY_REQUIRE
RUN composer global config --no-plugins allow-plugins.symfony/flex true \
    && composer global require --no-progress --no-scripts --no-plugins symfony/flex

ENV PATH="${PATH}:/usr/src/app/vendor/bin"
