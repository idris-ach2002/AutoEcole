#!/usr/bin/env sh
set -eu
find src migrations config public -name '*.php' -print0 | xargs -0 -n1 php -l
if [ -d vendor ]; then
  php bin/console lint:twig templates
  php bin/console lint:yaml config
  php bin/phpunit
  php bin/console app:quality-audit || true
else
  echo "vendor absent : les lints Symfony seront exécutés dans Docker."
fi
