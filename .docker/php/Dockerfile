FROM phpdockerio/php:8.3-fpm

ARG TIMEZONE

ENV SUPERVISOR_PHP_USER="root"

COPY php.ini /usr/local/etc/php/conf.d/docker-php-config.ini

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        php8.3-gd \
        php8.3-intl \
        php8.3-mbstring \
        php8.3-memcached \
        php8.3-amqp \
        php8.3-mysql \
        php8.3-sqlite \
        php8.3-oauth \
        php8.3-redis \
        php8.3-uuid \
        php8.3-xdebug \
        php8.3-yaml \
        supervisor \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
    && echo 'alias sf="php bin/console"' >> ~/.bashrc

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

USER root

RUN mkdir -p var/www/symfony/var/log var/www/symfony/var/cache/prod var/www/symfony/var/cache/dev var/www/symfony/var/cache/test var/log \
   && chown -R www-data:www-data var/www/symfony/var/ \
   && chmod -R ug+rwX var/www/symfony/var/

WORKDIR /var/www/symfony

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

