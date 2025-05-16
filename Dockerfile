
FROM php:8.1-apache
RUN apt-get update && apt-get install -y \
    libpng-dev \
    zlib1g-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    zip \
    vim \
    git \
    curl \
    unzip  \
    sudo \
    g++ \
    libpq-dev \
&& docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
&& docker-php-ext-install pdo_pgsql \
&& docker-php-ext-install zip \
&& docker-php-ext-install mbstring
WORKDIR /var/www/iwg
COPY apache/default.conf /etc/apache2/sites-available/my-apache-site.conf
RUN a2enmod rewrite &&\
    a2dissite 000-default.conf &&\
    a2ensite my-apache-site.conf &&\
    service apache2 restart
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY . .
ADD . /var/www/iwg
RUN groupadd -r user && useradd -r -g user user
RUN chmod -R 777 /var/run
RUN chown -R user:user /var/www
ENV APP_ENV=local
USER user
RUN composer install

