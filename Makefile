# Makefile

LIVE = --env-file ../supplymars-secrets/live.env -p supplymars-live -f compose.yaml -f compose.prod.yaml
PLAYGROUND = --env-file ../supplymars-secrets/playground.env -p supplymars-playground --profile playground -f compose.yaml -f compose.prod.yaml

# The production stacks run the images of the checked-out commit. := so a stale IMAGE_TAG in the
# shell cannot pick other images (a rollback would otherwise restart the bad version)
IMAGE_TAG := $(shell git rev-parse HEAD)

up:
	docker compose up --wait --build

up-prod-local:
	docker compose --env-file ../supplymars-secrets/prod-local.env -f compose.yaml -f compose.prod.yaml -f compose.prod.local.yaml up -d --build --remove-orphans --wait

# Run on the server. Pull the images CI built for the checked-out commit and start them; never
# build. Rollback: git reset --hard <previous-sha> && make up-prod-live. See Docs/11-runbook.md.
up-prod-live:
	IMAGE_TAG=$(IMAGE_TAG) docker compose $(LIVE) pull php nginx
	IMAGE_TAG=$(IMAGE_TAG) docker compose $(LIVE) up -d --no-build --remove-orphans --wait

up-prod-playground:
	IMAGE_TAG=$(IMAGE_TAG) docker compose $(PLAYGROUND) pull php nginx reset
	IMAGE_TAG=$(IMAGE_TAG) docker compose $(PLAYGROUND) up -d --no-build --remove-orphans --wait

# The deploy job's checks: /health answers on the port Caddy proxies to, and no migration is pending
verify-prod-live:
	curl -sf --retry 3 --retry-delay 5 http://$$(docker compose $(LIVE) port nginx 80)/health
	@echo
	docker compose $(LIVE) exec -T php php bin/console doctrine:migrations:up-to-date

verify-prod-playground:
	curl -sf --retry 3 --retry-delay 5 http://$$(docker compose $(PLAYGROUND) port nginx 80)/health
	@echo
	docker compose $(PLAYGROUND) exec -T php php bin/console doctrine:migrations:up-to-date

down-prod-live:
	docker compose $(LIVE) --profile '*' down --remove-orphans

down-prod-playground:
	docker compose $(PLAYGROUND) --profile '*' down --remove-orphans

certs:
	@command -v mkcert >/dev/null || { echo "mkcert not found — install with: brew install mkcert"; exit 1; }
	@rm -rf docker/nginx/certs/dev-fullchain.pem docker/nginx/certs/dev-privkey.pem
	mkcert -cert-file docker/nginx/certs/dev-fullchain.pem -key-file docker/nginx/certs/dev-privkey.pem localhost 127.0.0.1 ::1

# The services for symfony serve. Add rabbitmq if the project opts in to it (Docs/02-setup-local.md).
up-dev-tools:
	docker compose up -d --wait database redis mailer phpmyadmin phpmyadmin-prod-live phpmyadmin-prod-playground

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
# production compose files, for the live stack and the playground profile, and the nginx config,
# which otherwise only the publish and deploy jobs use. Compose warns about every unset production
# variable, so its output is shown only on failure.
check-prod-config:
	@docker compose -f compose.yaml -f compose.prod.yaml -f compose.publish.yaml config -q 2>/dev/null \
		|| docker compose -f compose.yaml -f compose.prod.yaml -f compose.publish.yaml config -q
	@docker compose --profile playground -f compose.yaml -f compose.prod.yaml -f compose.publish.yaml config -q 2>/dev/null \
		|| docker compose --profile playground -f compose.yaml -f compose.prod.yaml -f compose.publish.yaml config -q
	docker run --rm --add-host php:127.0.0.1 --entrypoint nginx -v $(CURDIR)/docker/nginx/conf.d/prod.conf:/etc/nginx/conf.d/default.conf:ro \
		$$(docker build -q --load --target nginx-dev -f docker/Dockerfile .) -t

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

.PHONY: up up-prod-local up-prod-live up-prod-playground verify-prod-live verify-prod-playground down-prod-live down-prod-playground certs up-dev-tools down test test-% check check-prod-config check-% bash logs logs-% clean-build
