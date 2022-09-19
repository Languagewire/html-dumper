# ----------------------------------------------------------------------------
# Install composer dependencies

FROM php:7.4.30-cli as base
RUN apt-get update && apt-get install -y \
  zip \
  && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
        && docker-php-ext-enable xdebug

WORKDIR /app

# Setup environment variables
ARG LIBRARY_RELEASE_NAME
ENV LIBRARY_RELEASE_NAME=$LIBRARY_RELEASE_NAME
ARG RUN_INTEGRATION_TESTS
ENV RUN_INTEGRATION_TESTS=$RUN_INTEGRATION_TESTS

COPY --from=composer:2.4.1 /usr/bin/composer /usr/local/bin/composer

COPY ./composer.json ./
COPY ./composer.lock ./

RUN sed -i "s/0.0.0/$LIBRARY_RELEASE_NAME/g" "composer.json"

RUN composer install --no-interaction --no-cache -o

COPY ./src ./src
COPY ./tests ./tests

COPY ./phpunit.xml ./
COPY ./phpstan.neon ./

RUN XDEBUG_MODE=coverage bin/phpunit \
    --coverage-html /publish/reports/coverage-html \
    --coverage-text=/publish/reports/coverage.txt \
    --colors \
    --testdox tests/unit/

RUN if [ "$RUN_INTEGRATION_TESTS" -eq 1 ]; then \
    bin/phpunit --colors --testdox tests/integration/ \
    ; fi

RUN bin/phpstan analyse -c phpstan.neon

RUN bin/phpcs --standard=PSR12 src/
RUN bin/phpcs --standard=PSR12 test/

RUN rm -rf ./bin ./vendor ./build ./environment ./vsts .phpunit.result.cache composer.lock

WORKDIR /publish
RUN cd /app && zip -r /publish/htmldumper-$LIBRARY_RELEASE_NAME.zip .
