# Docker development

Temuka runs as the isolated Compose project `temuka-dev`. PHP 8.3, Composer,
Artisan, and PHP tooling run in the `app` container. MySQL 8.0.40 uses a
project-scoped persistent volume. The database port is internal only; the API
binds to loopback on `TEMUKA_API_PORT` (default `8020`).

## First setup

From the backend directory:

```bash
cp .env.example .env
```

Replace `DB_PASSWORD` and `TEMUKA_DB_ROOT_PASSWORD` in the ignored `.env` with
independent local-only random values. Set `TEMUKA_API_PORT` if port 8020 is
already occupied. Do not use Larissama credentials or data paths.

Build the PHP image and install dependencies explicitly:

```bash
docker compose --env-file .env -p temuka-dev build app
docker compose --env-file .env -p temuka-dev run --rm --no-deps app composer install --no-interaction
```

Generate a local application key in the container:

```bash
docker compose --env-file .env -p temuka-dev run --rm --no-deps app php artisan key:generate --force
```

## Daily use

```bash
docker compose --env-file .env -p temuka-dev up -d --wait
docker compose --env-file .env -p temuka-dev exec -T app php artisan about
docker compose --env-file .env -p temuka-dev logs --tail=100 app db
docker compose --env-file .env -p temuka-dev stop
```

The migration command was not run during M1 verification. When migrations are
introduced, review them and run `docker compose --env-file .env -p temuka-dev
exec -T app php artisan migrate` only for development data.

Migrations persist in the Temuka development volume. The local API is at
`http://127.0.0.1:${TEMUKA_API_PORT:-8020}/api/v1/health`. No database host port
is published. The default API port and all Compose resource names can be
changed without touching other projects.

The Compose project name scopes the network and named volume; it is a collision
avoidance measure, not a Docker security boundary. Never run `down -v` against
an unverified project. Docker commands may require normal Codex approval in
this environment. If access is denied, report BLOCKED; do not use host PHP or
Composer, alter socket permissions, or change global approval settings.

Build, Composer installation, application key generation, startup, Artisan
about, health checks, database connectivity, logs, and stop/start have been
verified during M1. The migration command is the only development workflow
command listed above that was not run.
