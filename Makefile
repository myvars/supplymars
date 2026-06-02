# Makefile

PROJECT_NAME = symfony-app

up:
	docker compose up --wait --build

up-prod-local:
	docker compose --env-file ../supplymars-secrets/prod-local.env -f compose.yaml -f compose.prod.yaml -f compose.prod.local.yaml up -d --build --remove-orphans --wait

up-prod-live:
	docker compose --env-file ../supplymars-secrets/live.env -p supplymars-live -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait

down-prod-live:
	docker compose --env-file ../supplymars-secrets/live.env -p supplymars-live -f compose.yaml -f compose.prod.yaml down --remove-orphans

up-prod-playground:
	docker compose --env-file ../supplymars-secrets/playground.env -p supplymars-playground --profile playground -f compose.yaml -f compose.prod.yaml up -d --build --remove-orphans --wait

down-prod-playground:
	docker compose --env-file ../supplymars-secrets/playground.env -p supplymars-playground --profile playground -f compose.yaml -f compose.prod.yaml down --remove-orphans

certs:
	@command -v mkcert >/dev/null || { echo "mkcert not found — install with: brew install mkcert"; exit 1; }
	@rm -rf docker/nginx/certs/dev-fullchain.pem docker/nginx/certs/dev-privkey.pem
	mkcert -cert-file docker/nginx/certs/dev-fullchain.pem -key-file docker/nginx/certs/dev-privkey.pem localhost 127.0.0.1 ::1

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

.PHONY: up up-prod-local up-prod-live down-prod-live up-prod-playground down-prod-playground certs up-dev-tools down test test-% bash logs logs-% clean-build prune k6 k6-dash
