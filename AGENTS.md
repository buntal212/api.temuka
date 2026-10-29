# Temuka backend instructions

- Target Laravel 13, PHP 8.3, and MySQL 8.0.40 using InnoDB.
- Run PHP, Composer, Artisan, PHPUnit, and PHP quality tools only in Docker.
- Keep development on Compose project `temuka-dev`; run tests only on `temuka-test` from `compose.test.yaml`.
- Development data is persistent. Test data is disposable. Never run test migrations against development or production databases.
- Run relevant PHPUnit tests and Pint after code changes; report PASS, FAIL, BLOCKED, or NOT_RUN with command evidence.
- Use the installed global Laravel and Laravel database optimization skills where relevant; do not copy or install them here.
- Larissama is a read-only engineering reference. Do not copy its business rules, schema, or credentials.
- CI/CD and deployment are out of scope. Do not commit or push automatically.
