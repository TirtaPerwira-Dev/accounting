FROM php:8.3-apache

# Update paket
RUN apt-get update

# Install dependencies untuk intl, zip, gd
RUN apt-get install -y libicu-dev libzip-dev unzip libpng-dev libjpeg-dev libfreetype6-dev

# Install PostgreSQL drivers
RUN apt-get install -y libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Install MySQL client libraries and PDO MySQL
RUN apt-get install -y default-libmysqlclient-dev
RUN docker-php-ext-install pdo_mysql

# Install intl extension
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl

# Install zip extension
RUN docker-php-ext-install zip

# Install GD extension
RUN docker-php-ext-configure gd --with-jpeg --with-freetype
RUN docker-php-ext-install gd

# Enable Apache rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
