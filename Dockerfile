FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libldap2-dev \
    libssl-dev \
    libkrb5-dev \
    cron \
    git \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip ldap \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Set recommended PHP settings for SuiteCRM
RUN { \
    echo 'upload_max_filesize = 20M'; \
    echo 'post_max_size = 20M'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 600'; \
    echo 'max_input_time = 600'; \
    echo 'date.timezone = UTC'; \
    } > /usr/local/etc/php/conf.d/suitecrm.ini

# Download and install SuiteCRM
WORKDIR /var/www/html
RUN curl -L -o suitecrm.zip https://github.com/salesagility/SuiteCRM/archive/refs/tags/v7.14.2.zip \
    && unzip suitecrm.zip \
    && mv SuiteCRM-7.14.2/* . \
    && mv SuiteCRM-7.14.2/.* . 2>/dev/null || true \
    && rm -rf SuiteCRM-7.14.2 suitecrm.zip

# Create required directories
RUN mkdir -p custom/modules custom/Extension/application/Ext/Include \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 cache custom modules themes data upload config_override.php 2>/dev/null || true

# Copy custom modules and configurations
COPY ./custom-modules /var/www/html/custom/modules/
COPY ./install-scripts /usr/local/bin/
COPY ./config/config_override.php.template /var/www/html/

# Make scripts executable
RUN chmod +x /usr/local/bin/*.sh

# Setup cron for SuiteCRM scheduler
RUN echo "* * * * * www-data cd /var/www/html && php -f cron.php > /dev/null 2>&1" >> /etc/crontab

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
