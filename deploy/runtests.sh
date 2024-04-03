#!/bin/bash

/opt/bitnami/projects/app/bin/console doctrine:database:drop --env=test --force || true
/opt/bitnami/projects/app/bin/console doctrine:database:create --env=test
/opt/bitnami/projects/app/bin/console doctrine:schema:create --env=test -n

cd /opt/bitnami/projects/app
/opt/bitnami/php/bin/php /opt/bitnami/projects/app/bin/phpunit --testdox