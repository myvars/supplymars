---
title: Leaving the Bitnami box
period: April – June 2025
summary: From deploy scripts that asked for a domain name and every secret in turn, to Docker images and a GitHub Actions pipeline that tests and deploys on every push to main.
refs:
  - label: Runbook
    url: https://github.com/myvars/supplymars/blob/main/Docs/11-runbook.md
  - label: Operations
    url: https://github.com/myvars/supplymars/blob/main/Docs/06-operations.md
---

For its first year SupplyMars ran on a Bitnami server: Apache, PHP and MySQL installed on the machine, and the app checked out under `/opt/bitnami/projects/app`. Deploying meant logging in and running scripts. In April 2025 I rewrote those scripts. By June I had replaced them altogether.

## The scripts

After the April rewrite, `setup.sh` built a server from scratch, asking for the domain name and each secret and key in turn, nine values typed in by hand. `startup.sh` ran on every deploy: update the dependencies, migrate, build the assets, clear the cache, and restart the Messenger workers and Apache.

The rewrite made them strict: `set -eu`, a check for the commands setup needed, validation of every input, and a clear error and exit at each step that could fail. They were still scripts, run by hand, on one machine.

## Where the secrets live

In late April I moved the production secrets into Symfony's secrets vault: the database URL, the app secret, the mailer and S3 credentials, encrypted in the repository, with the decryption key kept off it. The scripts stopped asking for them.

When deploys moved to CI in June, the pipeline passed that decryption key to the server on every deploy. Within days I dropped the vault for plain environment variables, kept in GitHub's secrets and passed to the server by the deploy. Since March 2026 they have lived in env files on the server, outside the repository, and GitHub holds only what it needs to reach the server.

## Test factories in production

The simulation creates customers as it goes, and `app:create-customer-orders` built them with Foundry's `UserFactory` and `AddressFactory`, test tools. Foundry is a dev dependency, and its bundle is enabled only in dev and test, so in production the simulation depended on something that shouldn't be there. In June I fixed it properly: two small services, `RandomUserFactory` and `RandomAddressFactory`, build simulated customers and their addresses with Faker, and the command uses those. Test factories stay out of production code paths.

## Containers

In early June the app moved into Docker, so development, CI and production would run the same way. One multi-stage Dockerfile builds both images. For PHP, a base stage has PHP 8.3-FPM, its extensions and Composer; a dev stage adds Xdebug; and the prod stage installs dependencies without dev packages, drops the tests and dumps the environment. nginx has a dev and a prod stage of its own. Compose files describe each environment: `compose.yaml` for what every environment shares (PHP, nginx, MySQL and the Messenger worker), an override for development, and `compose.prod.yaml` for production, with the Let's Encrypt certificates mounted into nginx. The containers wait for the database, and an entrypoint script runs the migrations and sets up the Messenger transport.

The `deploy/` folder, its Apache virtual hosts and its Supervisor config were deleted. So was the per-domain branding the scripts used to copy into place; the app became SupplyMars on one domain.

## A pipeline

Days later came the first GitHub Actions workflow. On every push to `main` it builds the containers, runs the test suite inside them, and deploys over SSH to an AWS Lightsail server: pull, then `docker compose up --build --wait`. The Symfony 7.3 upgrade went out through it soon after.

It was one job, so a failing test stopped the deploy, but nothing ran before a change reached `main`.

*Update, January 2026: CI now also runs on every pull request, with testing and deploying as separate jobs; [the January roundup](/log/php-8-5-phpstan-7-and-writing-it-down) covers it. Since October 2026 the server no longer builds anything: CI builds the production images, pushes them to GitHub's container registry tagged with the commit, and the server pulls them.*
