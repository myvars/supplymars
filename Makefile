# Makefile

PROJECT_NAME = symfony-app
COMPOSE = docker compose

up:
	$(COMPOSE) up --wait --build

up-prod:
	APP_ENV=prod docker compose -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait

up-prod-local:
	docker compose --env-file .env.prod.local -f compose.yaml -f compose.prod.yaml -f compose.prod.local.yaml up -d --build --remove-orphans --wait

down:
	$(COMPOSE) down

restart:
	$(COMPOSE) down && $(COMPOSE) up --wait --build

migrate:
	$(COMPOSE) exec php php bin/console doctrine:migrations:migrate --no-interaction

messenger:
	$(COMPOSE) run --rm messenger

test:
	APP_ENV=test $(COMPOSE) run --rm php ./run-tests.sh

test-%:
	APP_ENV=test $(COMPOSE) run --rm php ./run-tests.sh --filter $*

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

up-nocache:
	$(COMPOSE) build --no-cache
	$(COMPOSE) up --wait

prune:
	docker system prune -af

.PHONY: up up-prod up-prod-local down restart init migrate messenger test test-% bash logs logs-% stop clean-build up-nocache prune
