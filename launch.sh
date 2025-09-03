#!/bin/bash

# Script universel pour installer PHP, Composer, Symfony CLI, PostgreSQL
# et lancer un projet Symfony existant "autoecole"
# Compatible avec Debian/Ubuntu, CentOS/Fedora, Arch, openSUSE

set -e

BASE_DIR="$(pwd)"
PROJECT_DIR="$BASE_DIR/autoecole"
LOG_FILE="$BASE_DIR/install_project.log"

echo "=== Début de l'installation complète ===" | tee "$LOG_FILE"

# -----------------------------
# Détection du gestionnaire de paquets
# -----------------------------
if [ -f /etc/debian_version ]; then
    PKG_MANAGER="apt"
elif [ -f /etc/redhat-release ]; then
    PKG_MANAGER="yum"
elif command -v dnf >/dev/null; then
    PKG_MANAGER="dnf"
elif command -v pacman >/dev/null; then
    PKG_MANAGER="pacman"
elif command -v zypper >/dev/null; then
    PKG_MANAGER="zypper"
else
    echo "Gestionnaire de paquets non supporté"
    exit 1
fi

echo "Gestionnaire de paquets détecté : $PKG_MANAGER" | tee -a "$LOG_FILE"

# -----------------------------
# Fonctions d'installation
# -----------------------------
install_package() {
    PACKAGE=$1
    case $PKG_MANAGER in
        apt)
            sudo apt update -y
            sudo apt install -y $PACKAGE
            ;;
        yum)
            sudo yum install -y $PACKAGE
            ;;
        dnf)
            sudo dnf install -y $PACKAGE
            ;;
        pacman)
            sudo pacman -S --noconfirm $PACKAGE
            ;;
        zypper)
            sudo zypper install -y $PACKAGE
            ;;
    esac
}

# -----------------------------
# Installer dépendances de base
# -----------------------------
echo "=== Installation des dépendances de base ===" | tee -a "$LOG_FILE"
if [ "$PKG_MANAGER" = "apt" ]; then
    install_package "software-properties-common ca-certificates lsb-release apt-transport-https wget unzip git curl gnupg"
elif [ "$PKG_MANAGER" = "dnf" ] || [ "$PKG_MANAGER" = "yum" ]; then
    install_package "wget unzip git curl gnupg2"
elif [ "$PKG_MANAGER" = "pacman" ]; then
    install_package "base-devel wget unzip git curl gnupg"
elif [ "$PKG_MANAGER" = "zypper" ]; then
    install_package "wget unzip git curl gpg2"
fi

# -----------------------------
# Installer PHP et extensions
# -----------------------------
echo "=== Installation de PHP et extensions essentielles ===" | tee -a "$LOG_FILE"
PHP_EXTENSIONS=("cli" "fpm" "pgsql" "mysql" "sqlite3" "mbstring" "xml" "curl" "gd" "intl" "bcmath" "zip" "opcache" "readline")

for ext in "${PHP_EXTENSIONS[@]}"; do
    if [ "$PKG_MANAGER" = "apt" ]; then
        install_package "php-$ext"
    elif [ "$PKG_MANAGER" = "dnf" ] || [ "$PKG_MANAGER" = "yum" ]; then
        install_package "php-$ext"
    elif [ "$PKG_MANAGER" = "pacman" ]; then
        install_package "php"
    elif [ "$PKG_MANAGER" = "zypper" ]; then
        install_package "php$ext"
    fi
done

# -----------------------------
# Installer Composer
# -----------------------------
if ! command -v composer >/dev/null; then
    echo "=== Installation de Composer ===" | tee -a "$LOG_FILE"
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

# -----------------------------
# Installer Symfony CLI
# -----------------------------
if ! command -v symfony >/dev/null; then
    echo "=== Installation de Symfony CLI ===" | tee -a "$LOG_FILE"
    wget https://get.symfony.com/cli/installer -O - | bash
    sudo mv ~/.symfony/bin/symfony /usr/local/bin/symfony
fi

# -----------------------------
# Installer PostgreSQL
# -----------------------------
echo "=== Installation de PostgreSQL ===" | tee -a "$LOG_FILE"
if [ "$PKG_MANAGER" = "apt" ]; then
    install_package "postgresql postgresql-contrib"
elif [ "$PKG_MANAGER" = "dnf" ] || [ "$PKG_MANAGER" = "yum" ]; then
    install_package "postgresql-server postgresql-contrib"
    sudo postgresql-setup --initdb
elif [ "$PKG_MANAGER" = "pacman" ]; then
    install_package "postgresql"
    sudo -u postgres initdb --locale en_US.UTF-8 -D /var/lib/postgres/data
elif [ "$PKG_MANAGER" = "zypper" ]; then
    install_package "postgresql-server postgresql-contrib"
    sudo systemctl enable postgresql
    sudo systemctl start postgresql
fi

sudo systemctl enable postgresql || true
sudo systemctl start postgresql || true

# Créer la base et l'utilisateur PostgreSQL
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

# -----------------------------
# Préparer le projet Symfony
# -----------------------------
echo "=== Préparation du projet Symfony ===" | tee -a "$LOG_FILE"
cd "$PROJECT_DIR"

composer install --no-interaction --prefer-dist >> "$LOG_FILE" 2>&1

php bin/console doctrine:migrations:migrate --no-interaction >> "$LOG_FILE" 2>&1

if [ -d "src/DataFixtures" ]; then
    php bin/console doctrine:fixtures:load --no-interaction >> "$LOG_FILE" 2>&1
fi

# -----------------------------
# Lancer le serveur Symfony
# -----------------------------
echo "=== Lancement du serveur Symfony ===" | tee -a "$LOG_FILE"
symfony server:start >> "$LOG_FILE" 2>&1 &

echo "=== Installation complète terminée ===" | tee -a "$LOG_FILE"
echo "Logs détaillés dans $LOG_FILE"
echo "Votre projet Symfony 'autoecole' est prêt et le serveur est lancé."

