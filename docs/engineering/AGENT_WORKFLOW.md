# AI agent workflow

Every change follows:

**DISCOVER → PLAN → IMPLEMENT → TEST → REVIEW → REPORT**

## DISCOVER

- Confirm the Temuka repository root and inspect Git status before edits.
- Read root `AGENTS.md`, relevant engineering guidance, and the source of
  truth for the affected API contract or business behavior.
- Inspect sibling implementations and tests. Treat Larissama as read-only
  engineering reference; do not copy its business policies, schemas, or
  credentials.
- Read the installed global `laravel-best-practices` and
  `laravel-database-optimization` skills when relevant. Do not install or copy
  global skills.

## PLAN

For non-trivial work, record briefly in the task plan:

- source of truth and affected API contracts;
- authorization scope and unresolved policy decisions;
- database schema/read-write impact;
- transaction, retry, and concurrency requirements;
- expected files and relevant global skills;
- focused tests, regression tests, and completion criteria.

If a business policy or contract is unresolved, identify it instead of
inventing a default.

## IMPLEMENT

- Stay within the authorized milestone and preserve existing user changes.
- Keep controllers focused on HTTP transport, use Form Requests for
  validation, Actions for meaningful use cases, and Resources for explicit
  response serialization.
- Add policies only for approved resource authority rules.
- Do not add speculative domain models, database tables, dependencies, or
  infrastructure.

## TEST

- Run focused PHPUnit tests after implementation, then relevant regression
  tests and the required full suite for milestone completion.
- Run PHP, Composer, Artisan, PHPUnit, and Pint only inside Docker.
- Use `temuka-test` and its disposable MySQL database. Never run test
  migrations against development or production data.
- If Docker or approval is unavailable, report BLOCKED. Do not fall back to
  host PHP or Composer.

## REVIEW

- Review the complete diff and check scope, API response contracts, field
  exposure, authorization, migration/index impact, and query behavior.
- Check formatting, ignored secrets/runtime files, and that only intended
  paths changed.
- Do not create commits, remotes, pushes, CI/CD, or deployments unless the
  user explicitly authorizes them.

## REPORT

Report each required check as **PASS**, **FAIL**, **BLOCKED**, or **NOT_RUN**.
Include the commands and actual evidence. A configuration-only result or a
historical test run is not proof that current behavior passed. State remaining
risks and decisions, and stop at the requested milestone.
