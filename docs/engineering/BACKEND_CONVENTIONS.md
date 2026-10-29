# Backend conventions

These conventions keep Temuka's API explicit and small while leaving business
decisions to the domain milestones that establish them.

## Application structure

- `app/Http/Controllers/Api/V1` handles HTTP transport and calls application
  operations.
- `app/Http/Requests/Api/V1` owns input validation and request-level
  authorization. Use Laravel's `FailOnUnknownFields` attribute when a body is
  a strict contract.
- `app/Actions` contains meaningful use cases or multi-step application logic.
  Keep small pure transformations in a focused class only when that makes the
  behavior independently testable. Do not add repositories, DTOs, or service
  layers without a concrete need.
- `app/Http/Resources/Api/V1` serializes explicit public fields. Do not return
  Eloquent models or expose internal attributes directly.
- `app/Policies` holds resource authorization when a real domain resource and
  authority model exist. No consumer/provider roles or policies are assumed
  in M2.

## HTTP and API contracts

- Version routes under `/api/v1` and name routes consistently.
- Controllers keep transport behavior, input binding, authorization
  boundaries, and response selection visible; delegate meaningful use cases
  to Actions.
- Form Requests define accepted fields and validation. Return the framework's
  JSON `422` validation shape for API requests: `message` and field-keyed
  `errors`.
- Success responses use a `data` envelope. Resources define its public
  contents. Paginated endpoints should use Laravel resource pagination so
  `data`, `links`, and `meta` remain explicit; establish filters and page
  limits with the endpoint contract.
- API exceptions render JSON for `/api/*` requests. Preserve established
  response contracts, including M1 health.
- The normalize example is an internal engineering reference. It is enabled
  only when `app.env` is `local` or `testing`, has no database dependency, and
  does not represent a Temuka product capability.

### Engineering reference endpoint

`POST /api/v1/examples/normalize` accepts a JSON object containing only
`value`, a non-empty string no longer than 1,000 characters. It returns HTTP
`200` with `data.original` unchanged and `data.normalized` trimmed with
consecutive Unicode or ordinary whitespace collapsed to one space. Invalid
input and unsupported fields return HTTP `422` with Laravel's `message` and
field-keyed `errors` JSON shape. Outside `local` and `testing`, the route
returns JSON `404`.

## Database and queries

- Use reviewed Laravel migrations with explicit types, foreign keys,
  uniqueness, nullability, and indexes that match known access patterns.
- MySQL 8.0.40 with InnoDB is the integration-test and development engine.
  Do not use SQLite to claim MySQL behavior.
- Keep related database writes in one short transaction. Make retry and
  concurrency behavior explicit before exposing a retryable operation.
- Load only required columns and relationships; avoid N+1 queries. Review
  representative query plans with MySQL `EXPLAIN ANALYZE` for critical read
  paths and verify useful indexes against actual predicates and ordering.
- Add no schema, cache, repository, or query abstraction for hypothetical
  future use.

## Authorization

- Form Requests may authorize a request when that decision is intrinsic to the
  operation; policies own resource-level decisions once a real resource and
  authority rules are approved.
- Controllers must not infer authority from client-supplied IDs or fields.
- If authority scope or denial behavior is unresolved, document the decision
  and stop that part of implementation rather than inventing roles.

## Testing

- Use PHPUnit. Unit tests cover deterministic Action logic; Feature tests call
  real HTTP routes and verify validation, authorization, status, and resource
  serialization.
- Test behavior through requests rather than checking only rule arrays.
- Run all PHP, Composer, Artisan, PHPUnit, and Pint commands inside Docker.
  Development uses `temuka-dev`; tests use only the disposable `temuka-test`
  MySQL 8.0.40 service.
- Keep tests deterministic. Use MySQL integration tests for behavior that
  depends on the selected database engine.
