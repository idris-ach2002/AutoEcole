#!/bin/bash

# Script ultra-automatique pour préparer un projet Symfony existant
# Installe les extensions PHP manquantes, Composer, les dépendances, migrations et fixtures

set -e

# Détecte la version de PHP
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "PHP version détectée : $PHP_VERSION"

# Liste des extensions essentielles Symfony
REQUIRED_EXTENSIONS=("pdo" "pdo_pgsql" "mbstring" "xml" "curl" "gd" "intl" "bcmath" "zip")

# Vérifie et installe les extensions manquantes
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "$ext"; then
        echo "Installation de l'extension PHP manquante : $ext"
        sudo apt install -y "php${PHP_VERSION}-${ext}"
    fi
done

# Vérifie Composer
if ! command -v composer >/dev/null; then
    echo "Composer n'est pas installé. Installation globale..."
    EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        echo 'ERREUR : le checksum de Composer ne correspond pas.'
        rm composer-setup.php
        exit 1
    fi
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

echo "=== Installation des dépendances du projet Symfony ==="
composer install --no-interaction --prefer-dist

echo "=== Exécution des migrations Doctrine ==="
php bin/console doctrine:migrations:migrate --no-interaction

echo "=== Chargement des fixtures (si présentes) ==="
if [ -d "src/DataFixtures" ]; then
    php bin/console doctrine:fixtures:load --no-interaction
else
    echo "Aucune fixture trouvée."
fi

echo "=== Projet Symfony prêt ==="

