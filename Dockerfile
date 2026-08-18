# テスト実行用イメージ。PHP のバージョンを引数で切り替えて 8.2 / 8.3 / 8.4 を通す。
#   docker build --build-arg PHP_VERSION=8.2 -t jp-invoice:8.2 .
#   docker run --rm -v "$PWD":/app jp-invoice:8.2 vendor/bin/phpunit
#
# 公式イメージに bcmath は同梱されていないので明示的に入れる。
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-cli

RUN docker-php-ext-install bcmath \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
