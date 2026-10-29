# Temuka backend instructions

- Target Laravel 13, PHP 8.3, and MySQL 8.0.40 using InnoDB.
- Run PHP, Composer, Artisan, PHPUnit, and PHP quality tools only in Docker.
- Keep development on Compose project `temuka-dev`; run tests only on `temuka-test` from `compose.test.yaml`.
- Development data is persistent. Test data is disposable. Never run test migrations against development or production databases.
- Run relevant PHPUnit tests and Pint after code changes; report PASS, FAIL, BLOCKED, or NOT_RUN with command evidence.
- Use the installed global Laravel and Laravel database optimization skills where relevant; do not copy or install them here.
- Larissama is a read-only engineering reference. Do not copy its business rules, schema, or credentials.
- CI/CD and deployment are out of scope. Do not commit or push automatically.

## Required agent workflow

Follow **DISCOVER → PLAN → IMPLEMENT → TEST → REVIEW → REPORT**. See
[`docs/engineering/AGENT_WORKFLOW.md`](docs/engineering/AGENT_WORKFLOW.md) for
the task checklist and reporting requirements.

For non-trivial work, identify the source of truth, affected API contracts,
authorization scope, database effects, transaction/concurrency requirements,
expected files, applicable global skills, tests, and acceptance criteria
before implementation. Do not invent unresolved business policies.

Use focused PHPUnit tests after changes and relevant regression coverage before
completion. Run every PHP, Composer, Artisan, PHPUnit, and Pint command inside
Docker. Never run test migrations against development or production databases.
Review the full diff and report actual verification evidence. Preserve user
changes and stop at the authorized milestone.
