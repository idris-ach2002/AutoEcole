#!/bin/bash

# Script complet pour installer PHP, Composer, Symfony CLI et PostgreSQL sur Ubuntu 22.04
# Création d'une base PostgreSQL pour Symfony

set -e

echo "=== Mise à jour du système ==="
sudo apt update -y
sudo apt upgrade -y

echo "=== Installation des dépendances de base ==="
sudo apt install -y software-properties-common ca-certificates lsb-release apt-transport-https wget unzip git curl gnupg

#######################
# INSTALLATION PHP
#######################
echo "=== Ajout du dépôt ondrej/php pour PHP ==="
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update -y

echo "=== Suppression des anciennes versions PHP (si présentes) ==="
sudo apt remove -y 'php*'
sudo apt autoremove -y

echo "=== Détection de la dernière version stable de PHP ==="
LATEST_PHP=$(apt-cache search ^php[0-9]\.[0-9]+$ | grep -oP '^php\K[0-9]+\.[0-9]+' | sort -V | tail -1)
echo "Dernière version stable détectée : PHP $LATEST_PHP"

echo "=== Installation de PHP $LATEST_PHP et extensions essentielles pour Symfony ==="
sudo apt install -y \
    php${LATEST_PHP} \
    php${LATEST_PHP}-cli \
    php${LATEST_PHP}-fpm \
    php${LATEST_PHP}-mysql \
    php${LATEST_PHP}-pgsql \
    php${LATEST_PHP}-sqlite3 \
    php${LATEST_PHP}-mbstring \
    php${LATEST_PHP}-xml \
    php${LATEST_PHP}-curl \
    php${LATEST_PHP}-gd \
    php${LATEST_PHP}-intl \
    php${LATEST_PHP}-bcmath \
    php${LATEST_PHP}-zip \
    php${LATEST_PHP}-opcache \
    php${LATEST_PHP}-readline \
    php${LATEST_PHP}-cli \
    php${LATEST_PHP}-dev \
    php${LATEST_PHP}-apcu

echo "=== Configuration PHP-FPM et php.ini ==="
PHP_INI_CLI="/etc/php/${LATEST_PHP}/cli/php.ini"
PHP_INI_FPM="/etc/php/${LATEST_PHP}/fpm/php.ini"

for ini in "$PHP_INI_CLI" "$PHP_INI_FPM"; do
    sudo sed -i "s/memory_limit = .*/memory_limit = 512M/" $ini
    sudo sed -i "s/max_execution_time = .*/max_execution_time = 300/" $ini
    sudo sed -i "s/error_reporting = .*/error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT/" $ini
    sudo sed -i "s/display_errors = .*/display_errors = Off/" $ini
done

sudo systemctl enable php${LATEST_PHP}-fpm
sudo systemctl restart php${LATEST_PHP}-fpm

#######################
# INSTALLATION COMPOSER
#######################
echo "=== Installation de Composer globalement ==="
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

#######################
# INSTALLATION SYMFONY CLI
#######################
echo "=== Installation de Symfony CLI globalement ==="
wget https://get.symfony.com/cli/installer -O - | bash
sudo mv ~/.symfony/bin/symfony /usr/local/bin/symfony

#######################
# INSTALLATION POSTGRESQL
#######################
echo "=== Ajout du dépôt officiel PostgreSQL ==="
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo gpg --dearmor -o /usr/share/keyrings/postgresql-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/postgresql-archive-keyring.gpg] http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" | sudo tee /etc/apt/sources.list.d/pgdg.list

sudo apt update -y
sudo apt install -y postgresql postgresql-contrib
sudo systemctl enable postgresql
sudo systemctl start postgresql

echo "=== Création de la base de données et de l'utilisateur PostgreSQL ==="
DB_NAME="idrisdatabase"
DB_USER="ai222829"
DB_PASS="Idris2023#"

sudo -i -u postgres psql <<EOF
DO
\$do\$
BEGIN
   IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '$DB_USER') THEN
      CREATE ROLE $DB_USER LOGIN PASSWORD '$DB_PASS';
   END IF;
END
\$do\$;

DO
\$do\$
BEGIN
   IF NOT EXISTS (SELECT FROM pg_database WHERE datname = '$DB_NAME') THEN
      CREATE DATABASE $DB_NAME OWNER $DB_USER;
   END IF;
END
\$do\$;

GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
EOF

echo "=== Installation complète terminée ==="
echo "PHP $LATEST_PHP, Composer, Symfony CLI et PostgreSQL sont installés globalement."
echo "Base de données '$DB_NAME' créée avec utilisateur '$DB_USER'."
echo "Vous pouvez maintenant créer vos projets Symfony et les connecter à PostgreSQL."

