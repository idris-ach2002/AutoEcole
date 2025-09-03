

#!/bin/bash

set -e

echo "=== Nettoyage complet de l'environnement Symfony ==="

# --- Suppression PHP ---
echo ">>> Suppression de PHP et extensions..."
sudo apt purge -y 'php*' || true
sudo apt autoremove -y
sudo rm -rf /etc/php

# --- Suppression Composer ---
echo ">>> Suppression de Composer..."
sudo rm -f /usr/local/bin/composer

# --- Suppression Symfony CLI ---
echo ">>> Suppression de Symfony CLI..."
sudo rm -f /usr/local/bin/symfony
sudo rm -rf ~/.symfony

# --- Suppression PostgreSQL ---
echo ">>> Suppression de PostgreSQL..."
sudo apt purge -y 'postgresql*' || true
sudo apt autoremove -y
sudo rm -rf /etc/postgresql /var/lib/postgresql
sudo deluser --remove-home postgres 2>/dev/null || true
sudo delgroup postgres 2>/dev/null || true

# --- Nettoyage cache apt ---
echo ">>> Nettoyage du cache apt..."
sudo apt clean

echo "=== Nettoyage terminé ==="
echo "Le système est maintenant propre. Vous pouvez relancer ./launch.sh"
 
