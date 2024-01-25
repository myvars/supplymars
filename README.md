# Butterfly

A simple code skeleton for a symfony project

## Setup

### Download Composer dependencies

Make sure you have [Composer installed](https://getcomposer.org/download/)
and then run:

```
composer install
```

### Database Setup

The code comes with a `compose.yaml` file (for Docker). 
Install PHP locally, but connect to a database inside Docker.

First, make sure you have [Docker installed](https://docs.docker.com/get-docker/)
and running. To start the container, run:

```
docker compose up -d
```

Next, build the database and the schema with:

```
symfony console doctrine:database:create --if-not-exists
symfony console doctrine:schema:create
symfony console doctrine:fixtures:load
```

If you're using something other than Postgresql, you can replace
`doctrine:migrations:migrate` with `doctrine:schema:update --force`.

If you do *not* want to use Docker, just make sure to start your own
database server and update the `DATABASE_URL` environment variable in
`.env` or `.env.local` before running the commands above.

### Tailwind bundle

This code includes the symfonycasts/tailwind-bundle to add tailwindcss.
Initialise your app with:

```
symfony console tailwind:init
```

The bundle works by swapping out the contents of styles/app.css with the compiled CSS automatically. For this to work, you need to run the tailwind:build command:

```
symfony console tailwind:build --watch
```

### Start the Symfony web server

Start the symfony web server, open a terminal, move into the
project, and run:

```
symfony serve -d
```

(If this is your first time using this command, you may see an
error that you need to run `symfony server:ca:install` first).

Now check out the site at `https://localhost:8000`

Happy coding!