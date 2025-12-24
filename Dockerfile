# On change l'image de base pour Apache (plus simple avec Traefik)
FROM php:8.2-apache

# Installation des dépendances système
# Note : on utilise apt-get car l'image apache est basée sur Debian, pas Alpine
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache

# ⚠️ IMPORTANT POUR SYMFONY
# 1. On active le mod_rewrite d'Apache (pour les URL propres)
RUN a2enmod rewrite

# 2. On change la racine du serveur web vers /public (standard Symfony)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier le projet
COPY . .

# Installer les dépendances
# On ajoute le paramètre pour permettre à Composer de tourner en root dans le conteneur
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --optimize-autoloader

# Changer les permissions pour qu'Apache puisse écrire dans var/ (cache/logs)
RUN chown -R www-data:www-data /var/www/html/var

# Apache écoute sur le port 80 par défaut
EXPOSE 80
CMD ["apache2-foreground"]