#!/bin/bash

# Clean up needed for StarterTheme tests
mysql -u root -e "DROP DATABASE IF EXISTS \`prestashop\`;"

echo "* Installing PrestaShop, this may take a while ...";
php install-dev/index_cli.php --language=en --country=fr --domain=localhost --db_server=127.0.0.1 --db_name=prestashop --db_create=1 --name=prestashop.unit.test --email=demo@prestashop.com --password=prestashop_demo
