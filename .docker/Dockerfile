FROM composer:2.5.5 as composer
FROM php:8.2-apache-buster as production

RUN apt-get update && apt-get upgrade -y && apt-get install -y \
  sudo  \
  vim \
  less \
  wget \
  iputils-ping  \
  default-mysql-client \
  libssl-dev  \
  libpng-dev  \
  libgmp-dev  \
  libc-client-dev  \
  libkrb5-dev  \
  libicu-dev \
  libmcrypt-dev  \
  libreadline-dev  \
  libldb-dev  \
  libldap2-dev  \
  libxml2-dev  \
  libtidy-dev  \
  libxslt-dev \
  libedit-dev \
  libmagickwand-dev \
  libzip-dev \
  git  \
  imagemagick \
  ssl-cert

RUN docker-php-ext-install bcmath
RUN docker-php-ext-install bz2
RUN docker-php-ext-install gd

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-install imap

RUN docker-php-ext-install intl
RUN docker-php-ext-install ldap
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install opcache

RUN docker-php-ext-install soap
RUN docker-php-ext-install tidy
RUN docker-php-ext-install xml
RUN docker-php-ext-install zip

RUN docker-php-ext-install pdo pdo_mysql

RUN pecl install imagick && docker-php-ext-enable imagick
RUN pecl install xdebug-3.2.1 && docker-php-ext-enable xdebug
RUN pecl install redis && docker-php-ext-enable redis

# Install PHP composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

FROM production
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

ENV APP_DIR /var/www

RUN rm /etc/apache2/sites-enabled/000-default.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
RUN echo "Listen 8081" >> /etc/apache2/ports.conf
RUN echo "Listen 8082" >> /etc/apache2/ports.conf
COPY .docker/apache2/vhosts/vhost.conf /etc/apache2/sites-enabled/vhost.conf

ENV LANG C.UTF-8
ENV LC_ALL C.UTF-8

RUN a2enmod rewrite
RUN a2enmod proxy
RUN a2enmod proxy_balancer
RUN a2enmod proxy_http
RUN a2enmod lbmethod_bybusyness
RUN a2enmod lbmethod_byrequests
RUN a2enmod lbmethod_bytraffic
RUN a2enmod lbmethod_heartbeat
RUN a2enmod headers
RUN a2enmod ssl

# add custom php ini file
COPY .docker/php/php-overwrite.ini $PHP_INI_DIR/conf.d/php-dav.ini

# set the application env
ENV APPLICATION_ENV development

WORKDIR $APP_DIR

### Development

# create writable directory for xdebug
RUN mkdir -p /tmp/xdebug/ \
  && chmod ugo+w /tmp/xdebug/

# Add user docker:docker
RUN useradd -m -u 1000 docker \
  && mkdir /home/docker/.ssh \
  && chown docker:docker /home/docker/.ssh

RUN make-ssl-cert generate-default-snakeoil --force-overwrite
RUN chown -R docker:docker /etc/ssl/certs/ /etc/ssl/private/

# Add fixuid for dynamic uid/gid switch
RUN USER=docker && \
  GROUP=docker && \
  curl -SsL https://github.com/boxboat/fixuid/releases/download/v0.5.1/fixuid-0.5.1-linux-amd64.tar.gz | tar -C /usr/local/bin -xzf - && \
  chown root:root /usr/local/bin/fixuid && \
  chmod 4755 /usr/local/bin/fixuid && \
  mkdir -p /etc/fixuid && \
  printf "user: $USER\ngroup: $GROUP\n" > /etc/fixuid/config.yml \
  printf "paths: []\n" >> /etc/fixuid/config.yml

COPY .docker/bin/docker-entrypoint-fixuid /usr/local/bin/docker-entrypoint-fixuid
RUN chmod a+x /usr/local/bin/docker-entrypoint-fixuid

# passwordless sudo for user docker
RUN printf '%s\n' 'docker ALL=(ALL) NOPASSWD: ALL' \
  > /etc/sudoers.d/docker-nopasswd && \
  chmod 440 /etc/sudoers.d/docker-nopasswd

USER docker:docker
ENTRYPOINT ["docker-entrypoint-fixuid", "docker-php-entrypoint"]
CMD ["apache2-foreground"]
