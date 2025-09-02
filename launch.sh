#!/bin/bash

# Script final non-interactif pour installer la stack complète et lancer le projet Symfony "autoecole"
# Tout est automatisé : PHP, Composer, Symfony CLI, PostgreSQL, migrations et fixtures

set -e

BASE_DIR="$(pwd)"
PROJECT_DIR="$BASE_DIR/autoecole"
LOG_FILE="$BASE_DIR/install_project.log"

echo "=== Début de l'installation complète ===" | tee "$LOG_FILE"

# --- INSTALLATION DE LA STACK ---
echo "=== INSTALLATION DE LA STACK COMPLETE ===" | tee -a "$LOG_FILE"
bash "$BASE_DIR/install_full_stack.sh" >> "$LOG_FILE" 2>&1

# --- PREPARATION DU PROJET SYMFONY ---
echo "=== PREPARATION DU PROJET SYMFONY ===" | tee -a "$LOG_FILE"
cd "$PROJECT_DIR"

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "PHP version détectée : $PHP_VERSION" | tee -a "$LOG_FILE"

REQUIRED_EXTENSIONS=("pdo" "pdo_pgsql" "mbstring" "xml" "curl" "gd" "intl" "bcmath" "zip")

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "$ext"; then
        echo "Installation de l'extension PHP manquante : $ext" | tee -a "$LOG_FILE"
        sudo apt install -y "php${PHP_VERSION}-${ext}" >> "$LOG_FILE" 2>&1
    fi
done

# Composer
if ! command -v composer >/dev/null; then
    echo "Installation de Composer globalement..." | tee -a "$LOG_FILE"
    EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        echo 'ERREUR : le checksum de Composer ne correspond pas.' | tee -a "$LOG_FILE"
        rm composer-setup.php
        exit 1
    fi
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer >> "$LOG_FILE" 2>&1
    rm composer-setup.php
fi

# Composer install
echo "=== Installation des dépendances du projet ===" | tee -a "$LOG_FILE"
composer install --no-interaction --prefer-dist >> "$LOG_FILE" 2>&1

# Doctrine migrations
echo "=== Exécution des migrations Doctrine ===" | tee -a "$LOG_FILE"
php bin/console doctrine:migrations:migrate --no-interaction >> "$LOG_FILE" 2>&1

# Fixtures
if [ -d "src/DataFixtures" ]; then
    echo "=== Chargement des fixtures ===" | tee -a "$LOG_FILE"
    php bin/console doctrine:fixtures:load --no-interaction >> "$LOG_FILE" 2>&1
else
    echo "Aucune fixture trouvée." | tee -a "$LOG_FILE"
fi

# Lancement du serveur Symfony
echo "=== LANCEMENT DU SERVEUR SYMFONY ===" | tee -a "$LOG_FILE"
symfony server:start >> "$LOG_FILE" 2>&1 &

echo "=== INSTALLATION COMPLETE ===" | tee -a "$LOG_FILE"
echo "Logs détaillés disponibles dans $LOG_FILE"
echo "Votre projet Symfony 'autoecole' est prêt et le serveur est lancé."

