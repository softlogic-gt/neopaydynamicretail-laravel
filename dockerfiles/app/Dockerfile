FROM ubuntu:24.04 AS keyring

ARG ONDREJ_PHP_FINGERPRINT=B8DC7E53946656EFBCE4C1DD71DAEAAB4AD4CAB6

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends ca-certificates curl gnupg \
    && curl -fsSL "https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x${ONDREJ_PHP_FINGERPRINT}" | gpg --dearmor -o /ondrej-php.gpg \
    && gpg --show-keys --with-colons /ondrej-php.gpg | awk -F: '$1 == "fpr" { print $10; exit }' | grep -qx "${ONDREJ_PHP_FINGERPRINT}"

FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC \
    COMPOSER_HOME=/tmp/composer

COPY --from=keyring /ondrej-php.gpg /etc/apt/keyrings/ondrej-php.gpg

RUN ln -snf /usr/share/zoneinfo/UTC /etc/localtime && echo UTC > /etc/timezone \
    && apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates \
    && echo "deb [signed-by=/etc/apt/keyrings/ondrej-php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ondrej-php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip \
        php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN git config --system --add safe.directory /var/www

WORKDIR /var/www
