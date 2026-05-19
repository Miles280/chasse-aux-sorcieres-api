FROM php:8.2-apache

# Installation des dépendances système + nettoyage du cache apt dans la même foulée (gain de place)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configuration Apache & Symfony
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Optimisation Symfony pour la prod
ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1

# SOLUTION : On copie TOUT le projet en attribuant DIRECTEMENT les droits à www-data
COPY --chown=www-data:www-data . .

# On s'assure que le dossier var existe avec les bons droits avant composer
RUN mkdir -p /var/www/html/var && chown -R www-data:www-data /var/www/html/var

# Installer les dépendances. Composer va s'exécuter en créant les fichiers directement au bon format
RUN composer install --no-dev --no-interaction --optimize-autoloader

EXPOSE 80
CMD ["apache2-foreground"]