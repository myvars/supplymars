# Makefile

PROJECT_NAME = symfony-app
COMPOSE = docker compose

up:
	$(COMPOSE) up --wait --build

up-prod:
	APP_ENV=prod docker compose -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait

up-prod-local:
	docker compose --env-file .env.prod.local -f compose.yaml -f compose.prod.yaml -f compose.prod.local.yaml up -d --build --remove-orphans --wait

up-dev-tools:
	$(COMPOSE) -f compose.dev-tools.yaml up -d --wait --build

down:
	$(COMPOSE) -f compose.yaml -f compose.dev-tools.yaml down --remove-orphans

migrate:
	$(COMPOSE) exec php php bin/console doctrine:migrations:migrate --no-interaction

messenger:
	$(COMPOSE) run --rm messenger

test:
	$(COMPOSE) run --rm -e APP_ENV=test php ./run-tests.sh

test-%:
	$(COMPOSE) run --rm -e APP_ENV=test php ./run-tests.sh --filter $*

bash:
	$(COMPOSE) exec php bash

logs:
	$(COMPOSE) logs -f

logs-%:
	$(COMPOSE) logs -f $*

stop:
	$(COMPOSE) stop

clean-build:
	$(COMPOSE) build --no-cache

prune:
	docker system prune -af

cache-clear:
	$(COMPOSE) exec php php bin/console cache:clear

.PHONY: up up-prod up-prod-local up-dev-tools down migrate messenger test test-% bash logs logs-% stop clean-build prune cache-clear
