#!/bin/bash
read -p $'Drop existing database? [n]:\n' input
choice=${input:-n}

# Composer install
composer update --working-dir /opt/bitnami/projects/app/

# Compile asset mapper
cd /opt/bitnami/projects/app
/opt/bitnami/projects/app/bin/console tailwind:build --minify
/opt/bitnami/projects/app/bin/console asset-map:compile


if  [ "$choice" = "y" -o "$choice" = "Y" ] ;then
echo "Dropping database..."
# Create database migrations
/opt/bitnami/projects/app/bin/console doctrine:database:drop --force || true
/opt/bitnami/projects/app/bin/console doctrine:database:create
/opt/bitnami/projects/app/bin/console doctrine:schema:create
# App specific startup options ##########################################
# Load fixtures
/opt/bitnami/projects/app/bin/console doctrine:fixtures:load -n --env=dev --no-debug
else
echo "Using existing data"
/opt/bitnami/projects/app/bin/console doctrine:migrations:migrate -n
fi

# Install supervisor scripts
sudo supervisorctl stop messenger-consume:*
sudo cp /opt/bitnami/projects/app/deploy/messenger-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start messenger-consume:*
# End App specific startup options ######################################

# Restart apache
sudo /opt/bitnami/ctlscript.sh restart apache