# Testing

Tests use PHPUnit in the isolated Compose project `temuka-test`. That project
has its own network and a fresh MySQL 8.0.40 container with no published port.
MySQL stores data in a container tmpfs, with no persistent volume. It does not
load `.env`, use the development Compose file, or share its database
credentials.

## Test workflow

```bash
docker compose -p temuka-test -f compose.test.yaml build test-runner
docker compose -p temuka-test -f compose.test.yaml up -d --wait test-db
docker compose -p temuka-test -f compose.test.yaml run --rm -T test-runner php artisan temuka:verify-test-database
docker compose -p temuka-test -f compose.test.yaml run --rm -T test-runner php artisan test --filter=HealthEndpointTest
docker compose -p temuka-test -f compose.test.yaml run --rm -T test-runner php artisan test
docker compose -p temuka-test -f compose.test.yaml run --rm -T test-runner vendor/bin/pint --test
docker compose -p temuka-test -f compose.test.yaml run --rm -T test-runner composer validate --strict
docker compose -p temuka-test -f compose.test.yaml down --remove-orphans
```

For focused M2 checks and M1 regression coverage, use:

```bash
docker compose -p temuka-test -f compose.test.yaml run --rm -T test-runner php artisan test tests/Unit/NormalizeExampleValueTest.php
docker compose -p temuka-test -f compose.test.yaml run --rm -T test-runner php artisan test tests/Feature/NormalizeExampleApiTest.php
docker compose -p temuka-test -f compose.test.yaml run --rm -T test-runner php artisan test --filter='HealthEndpointTest|ApiErrorResponseTest|TestDatabaseConnectionTest|TestDatabaseSafetyTest'
```

The MySQL health check gates the test runner without a fixed sleep. Before
PHPUnit test traits can run migrations, the Laravel test case verifies
`APP_ENV`, the MySQL connection, exact service hostname, port, database name,
user, empty `DB_URL`, and non-empty password; it then queries the connected
server for database identity, MySQL 8.0.40, and InnoDB. PHPUnit configuration
forces its test-only settings, while the Compose runner injects test-only
credentials directly and never inherits the development `.env`.

The test-runner entrypoint also runs this preflight before direct Artisan
migrate, rollback, wipe, and seed commands. An intentionally unapproved host
was rejected before a `migrate --pretend` command could connect or execute.

`RefreshDatabase` and other test migrations operate only on the disposable
`temuka_test` instance. The test database container has no named or bind
volume; removing the test project discards it. Teardown is scoped by both the
project name and Compose file and deliberately omits `-v`.

The engineering reference endpoint is enabled only when `app.env` is `local`
or `testing`; its feature test sets `production` in application configuration
and confirms a JSON `404` without contacting a production service. To inspect
the development routes and make an HTTP request against the local API, use:

```bash
docker compose --env-file .env -p temuka-dev exec -T app php artisan route:list --path=api/v1
docker compose --env-file .env -p temuka-dev exec -T app php -r '$payload=json_encode(["value"=>"  Temuka    Marketplace  "]); $context=stream_context_create(["http"=>["method"=>"POST","header"=>"Content-Type: application/json\r\nAccept: application/json\r\n","content"=>$payload,"ignore_errors"=>true]]); $response=file_get_contents("http://127.0.0.1:8000/api/v1/examples/normalize", false, $context); echo $http_response_header[0], PHP_EOL, $response, PHP_EOL;'
```

This local HTTP check is separate from PHPUnit and uses the development app
only. It does not run migrations or access production credentials.

If Docker is unavailable or approval is denied, mark verification BLOCKED.
Never fall back to host PHP/Composer or run test commands using the development
Compose project. Report PHPUnit, Pint, Composer validation, and database
preflight as PASS, FAIL, BLOCKED, or NOT_RUN with their command outputs.

These commands are the proposed local workflow; actual verified results are
recorded in the M1 completion report.
