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

COPY --from=composer:2.4.1 /usr/bin/composer /usr/local/bin/composer

COPY ./composer.json ./
COPY ./composer.lock ./

RUN sed -i "s/0.0.0/$LIBRARY_RELEASE_NAME/g" "composer.json"

# Uncomment to update composer.lock file
# RUN composer update
# RUN cp ./composer.lock ./composer.lock.bak
# RUN cp -r ./vendor ./vendor.bak

RUN composer install --no-interaction --no-cache -o

# ----------------------------------------------------------------------------

FROM base as build

ARG RUN_INTEGRATION_TESTS
ENV RUN_INTEGRATION_TESTS=$RUN_INTEGRATION_TESTS

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

FROM mcr.microsoft.com/azure-cli:2.37.0 as publish

ARG LIBRARY_RELEASE_NAME
ARG DO_PUBLISH
# To be able to publish an artifact to Azure we need to supply a Personal Access Token (PAT). You can generate one in
# your user settings space. We need Read and Write permissions to Packages only.
ARG AZURE_DEVOPS_EXT_PAT

ENV LIBRARY_RELEASE_NAME=$LIBRARY_RELEASE_NAME
ENV DO_PUBLISH=$DO_PUBLISH
ENV AZURE_DEVOPS_EXT_PAT=$AZURE_DEVOPS_EXT_PAT

WORKDIR /library
COPY --from=build /app ./
COPY --from=build /publish /publish

RUN rm -rf ./bin ./vendor ./build ./environment ./vsts .phpunit.result.cache composer.lock

WORKDIR /publish
RUN cd /library && zip -r /publish/htmldumper.zip .

# Publish the library as an artifact
RUN if [ "$DO_PUBLISH" -eq 1 ]; then \
    az extension add --name azure-devops; \
    az artifacts universal publish \
        --organization https://dev.azure.com/languagewire/ \
        --project="connectors" \
        --scope project \
        --feed connectors \
        --name htmldumper \
        --version $LIBRARY_RELEASE_NAME \
        --description "Static Page Generator" \
        --path . \
    ; fi

