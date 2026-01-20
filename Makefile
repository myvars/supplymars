# Makefile

PROJECT_NAME = symfony-app

up:
	docker compose up --wait --build

up-prod:
	APP_ENV=prod docker compose -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait

up-prod-local:
	docker compose --env-file ../supplymars-secrets/prod-local.env -f compose.yaml -f compose.prod.yaml -f compose.prod.local.yaml up -d --build --remove-orphans --wait

up-dev-tools:
	docker compose -f compose.dev-tools.yaml up -d --wait --build

down:
	docker compose -f compose.yaml -f compose.dev-tools.yaml down --remove-orphans

test:
	docker compose run --rm -e APP_ENV=test php ./scripts/run-tests.sh

test-%:
	docker compose run --rm -e APP_ENV=test php ./scripts/run-tests.sh --filter $*

bash:
	docker compose exec php bash

logs:
	docker compose logs -f

logs-%:
	docker compose logs -f $*

clean-build:
	docker compose build --no-cache

prune:
	docker system prune -af

k6:
	./scripts/run-k6-script.sh $(SCRIPT) $(ENV) false

k6-dash:
	./scripts/run-k6-script.sh $(SCRIPT) $(ENV) true

.PHONY: up up-prod up-prod-local up-dev-tools down test test-% bash logs logs-% clean-build prune k6 k6-dash
