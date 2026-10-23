# Makefile

# The base image of docker/Dockerfile, for checking the Caddyfile without building the app
FRANKENPHP_IMAGE = $(shell sed -n 's/^FROM \(dunglas\/frankenphp:[^ ]*\).*/\1/p' docker/Dockerfile)

# Placeholder values for the variables the Caddyfile's Mercure hub reads, which it cannot parse without
CADDY_CHECK_ENV = -e MERCURE_JWT_ISSUER=https://example.com -e MERCURE_PUBLIC_URL=https://example.com/.well-known/mercure \
	-e MERCURE_JWT_SECRET=check-prod-config-placeholder-secret-32

LIVE = --env-file ../supplymars-secrets/live.env -p supplymars-live -f compose.yaml -f compose.prod.yaml
PLAYGROUND = --env-file ../supplymars-secrets/playground.env -p supplymars-playground --profile playground -f compose.yaml -f compose.prod.yaml

# The production stacks run the images of the checked-out commit. := so a stale IMAGE_TAG in the
# shell cannot pick other images (a rollback would otherwise restart the bad version)
IMAGE_TAG := $(shell git rev-parse HEAD)

up:
	docker compose up --wait --build

# FrankenPHP does not start without MERCURE_JWT_SECRET (its Caddyfile cannot be parsed), so the three
# targets below check the env file first, before any running container is replaced. Not ${VAR:?} in
# compose.prod.yaml: Compose would demand it for every command, the publish job's build included.
up-prod-local:
	@grep -qE '^MERCURE_JWT_SECRET=.{32,}' ../supplymars-secrets/prod-local.env || { echo "MERCURE_JWT_SECRET (32+ characters) is missing from ../supplymars-secrets/prod-local.env. See Docs/11-runbook.md"; exit 1; }
	docker compose --env-file ../supplymars-secrets/prod-local.env -f compose.yaml -f compose.prod.yaml -f compose.prod.local.yaml up -d --build --remove-orphans --wait

# Run on the server. Pull the images CI built for the checked-out commit and start them; never
# build. Rollback: git reset --hard <previous-sha> && make up-prod-live. See Docs/11-runbook.md.
up-prod-live:
	@grep -qE '^MERCURE_JWT_SECRET=.{32,}' ../supplymars-secrets/live.env || { echo "MERCURE_JWT_SECRET (32+ characters) is missing from ../supplymars-secrets/live.env. See Docs/11-runbook.md"; exit 1; }
	IMAGE_TAG=$(IMAGE_TAG) docker compose $(LIVE) pull php
	IMAGE_TAG=$(IMAGE_TAG) docker compose $(LIVE) up -d --no-build --remove-orphans --wait

up-prod-playground:
	@grep -qE '^MERCURE_JWT_SECRET=.{32,}' ../supplymars-secrets/playground.env || { echo "MERCURE_JWT_SECRET (32+ characters) is missing from ../supplymars-secrets/playground.env. See Docs/11-runbook.md"; exit 1; }
	IMAGE_TAG=$(IMAGE_TAG) docker compose $(PLAYGROUND) pull php reset
	IMAGE_TAG=$(IMAGE_TAG) docker compose $(PLAYGROUND) up -d --no-build --remove-orphans --wait

# The deploy job's checks: /health answers on the port Caddy proxies to, and no migration is pending
verify-prod-live:
	curl -sf --retry 3 --retry-delay 5 http://$$(docker compose $(LIVE) port php 80)/health
	@echo
	docker compose $(LIVE) exec -T php php bin/console doctrine:migrations:up-to-date

verify-prod-playground:
	curl -sf --retry 3 --retry-delay 5 http://$$(docker compose $(PLAYGROUND) port php 80)/health
	@echo
	docker compose $(PLAYGROUND) exec -T php php bin/console doctrine:migrations:up-to-date

down-prod-live:
	docker compose $(LIVE) --profile '*' down --remove-orphans

down-prod-playground:
	docker compose $(PLAYGROUND) --profile '*' down --remove-orphans

# The services for symfony serve. Add rabbitmq if the project opts in to it (Docs/02-setup-local.md).
up-dev-tools:
	docker compose up -d --wait database redis mailer mercure phpmyadmin phpmyadmin-prod-live phpmyadmin-prod-playground

# --profile '*' also removes opt-in services (RabbitMQ) whether or not their profile is on
down:
	docker compose --profile '*' down --remove-orphans

test:
	docker compose run --rm -e APP_ENV=test -e TEST_PROCESSES php ./scripts/run-tests.sh

test-%:
	docker compose run --rm -e APP_ENV=test -e TEST_PROCESSES php ./scripts/run-tests.sh --filter $*

# Every check CI runs. check-<step> runs one: prod-config, cs, rector, phpstan, deptrac, lint, audit, migrations, tests
check: check-prod-config
	docker compose run --rm -e APP_ENV=test -e TEST_PROCESSES php ./scripts/check.sh

# Runs on the host (scripts/check.sh runs in the php container, which has no Docker). Checks the
# production compose files, for the live stack and the playground profile, and the Caddyfile in both
# its modes (development's localhost and php:80, production's :80), which otherwise only the publish and deploy
# jobs use, then production's worker mode with the FRANKENPHP_CONFIG compose.prod.yaml sets. Compose
# warns about every unset production variable, and Caddy logs every step, so their output is shown only
# on failure.
check-prod-config:
	@docker compose -f compose.yaml -f compose.prod.yaml -f compose.publish.yaml config -q 2>/dev/null \
		|| docker compose -f compose.yaml -f compose.prod.yaml -f compose.publish.yaml config -q
	@docker compose --profile playground -f compose.yaml -f compose.prod.yaml -f compose.publish.yaml config -q 2>/dev/null \
		|| docker compose --profile playground -f compose.yaml -f compose.prod.yaml -f compose.publish.yaml config -q
	@for server_name in 'localhost, php:80' :80; do \
		out=$$(docker run --rm -e SERVER_NAME="$$server_name" $(CADDY_CHECK_ENV) -v $(CURDIR)/docker/frankenphp/Caddyfile:/etc/frankenphp/Caddyfile:ro \
			$(FRANKENPHP_IMAGE) frankenphp validate --config /etc/frankenphp/Caddyfile 2>&1) || { echo "$$out"; exit 1; }; \
		echo "Caddyfile valid with SERVER_NAME=$$server_name"; \
	done
	@worker=$$(docker compose -f compose.yaml -f compose.prod.yaml config php 2>/dev/null | sed -n 's/^ *FRANKENPHP_CONFIG: //p'); \
	[ -n "$$worker" ] || { echo "No FRANKENPHP_CONFIG for php in compose.prod.yaml"; exit 1; }; \
	out=$$(docker run --rm -e SERVER_NAME=:80 -e FRANKENPHP_CONFIG="$$worker" $(CADDY_CHECK_ENV) -v $(CURDIR)/docker/frankenphp/Caddyfile:/etc/frankenphp/Caddyfile:ro \
		-v $(CURDIR)/public:/app/public:ro $(FRANKENPHP_IMAGE) frankenphp validate --config /etc/frankenphp/Caddyfile 2>&1) || { echo "$$out"; exit 1; }; \
	echo "Caddyfile valid with FRANKENPHP_CONFIG=$$worker"

check-%:
	docker compose run --rm -e APP_ENV=test -e TEST_PROCESSES php ./scripts/check.sh $*

bash:
	docker compose exec php bash

logs:
	docker compose logs -f

logs-%:
	docker compose logs -f $*

clean-build:
	docker compose build --no-cache

.PHONY: up up-prod-local up-prod-live up-prod-playground verify-prod-live verify-prod-playground down-prod-live down-prod-playground up-dev-tools down test test-% check check-prod-config check-% bash logs logs-% clean-build
