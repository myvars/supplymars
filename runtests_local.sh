#!/bin/bash

symfony console doctrine:database:drop --env=test --force || true
symfony console doctrine:database:create --if-not-exists --env=test
symfony console doctrine:schema:create --env=test
#symfony console doctrine:fixtures:load --env=test -n
#php bin/phpunit --testdox