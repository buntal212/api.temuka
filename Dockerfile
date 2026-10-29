FROM php:8.3-cli-bookworm@sha256:6e25fbb304ba6641eb4d2a53e92bea8eeba8c0d0eabf6ca41d0f1ac3b21a9ef0

ARG TEMUKA_UID=1000
ARG TEMUKA_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        dom \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        xml \
        zip \
    && groupadd --gid "${TEMUKA_GID}" temuka \
    && useradd --uid "${TEMUKA_UID}" --gid temuka --create-home --shell /bin/bash temuka \
    && mkdir -p /tmp/composer \
    && chown -R temuka:temuka /tmp/composer \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2@sha256:b6dcd72ca98fa19d0e5b8476de179859704891fad06dd9e0b96833d4c430a5fc /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
USER temuka

ENV COMPOSER_HOME=/tmp/composer
