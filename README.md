# ExaPHP DDD/CQRS Example Project

An example PHP application built with ExaPHP to demonstrate Domain-Driven Design (DDD) and Command Query Responsibility
Segregation (CQRS).

Development is coordinated by a developer and supported by AI through instructions and prompts. The developer sets the
direction, defines requirements, and guides architectural decisions. AI assists with implementation, tests, and
documentation using task-specific prompts and the project conventions in [AGENTS.md](AGENTS.md).

This project is a work in progress. It currently provides the application bootstrap, a public API information endpoint,
authorization for its self-link, and automated tests. A complete DDD/CQRS use case is still
planned; see [TODO.md](TODO.md).

## Getting started

Install Docker with Docker Compose and the `just` command runner. PHP 8.5, Composer, nginx, and MySQL 8.4 run in containers; local
PHP and Composer installations are not required.

From the project root, run:

```sh
just setup
```

This creates `.env` from `.env.dist` if needed, builds the images, installs dependencies, and starts the services.
To customize database settings before the first startup, run `just env-init` and edit `.env` before `just setup`.
Open [http://localhost/v1](http://localhost/v1)
to view the API information as HAL JSON. Port 80 must be available on the host.

To stop the services:

```sh
just docker-down
```

Run `just` to list the available recipes.

To configure a PDO connection, copy `config/pdo.local.php.dist` to `config/pdo.local.php`. The template reads
`MYSQL_DATABASE`, `MYSQL_USER`, and `MYSQL_PASSWORD` through `getenv()`. Compose passes these settings from `.env` to
both PHP and MySQL; `MYSQL_ROOT_PASSWORD` is passed only to MySQL. The defaults in `.env.dist` are for local development.
The `.env` file and local PHP config files are ignored by Git. The PHP image includes `pdo_mysql`; PDO is created only
when requested from the service locator through `PDO::class`.

PHP waits for MySQL's health check to successfully query the configured database before starting. MySQL is accessible
within the Docker network on port 3306 and stores data in the `mysql-data` volume, which survives `just docker-down`.
Database names and credentials in `.env` initialize an empty volume; changing them does not update an existing database.

For an IDE database connection, use host `127.0.0.1`, port `3306`, and the database name, username, and password from
`MYSQL_DATABASE`, `MYSQL_USER`, and `MYSQL_PASSWORD` in `.env`. MySQL's published port is bound to localhost.
Run `just docker-up` to apply the port mapping to an existing container.

### Initialize database tables

MySQL automatically creates tables on first startup with an empty `mysql-data` volume, including during `just setup`.
Each module owns a `resources/database/schema.sql` file mounted under `/docker-entrypoint-initdb.d/` in `compose.yml`.
Add a volume mount for each module's schema, using numbered destination filenames such as `010-task.sql` to control
execution order.

Initialization scripts run only for an empty data volume. Restarting containers, adding schema mounts, or changing SQL
files does not update an existing database. Apply changes manually to existing databases. To rebuild a disposable local
database from the schema files, run the following commands; this deletes all existing database data:

```sh
just docker-down --volumes
just docker-up
```

Store timestamps in UTC; the schema uses `DATETIME(6)` to preserve microseconds and expects writes to supply timestamps.
The Task module provides a MySQL PDO repository implementation. Construct `PdoTaskRepository` with
an exception-mode PDO connection. Task creation receives time from the handler’s injected clock; the repository persists
and restores that timestamp and preserves it on updates. Callers own transaction boundaries; a durable outbox must use the same connection to make
state and event storage atomic.

## Architecture

Modules live under `module/<Module>/`. As use cases are introduced, they follow these boundaries:

- `Domain`: entities, value objects, domain rules, events, and repository interfaces, independent of the framework.
- `Application`: commands, queries, handlers, and use-case contracts. Commands may change state; queries are side effect
  free.
- `Infrastructure`: HTTP controllers, persistence, messaging, and ExaPHP adapters.

Dependencies point inward toward the domain. Module configuration lives in `config/` within each module, while
composition classes such as `ApplicationFactory` and `ApplicationModule` live directly under the module's `src/`
directory.

The `Shared` module contains framework-independent DDD building blocks used across modules. It provides domain event
contracts and an optional aggregate base class for event collection. Its registered `SharedModule` loads shared service
bindings from `module/Shared/config/`. These local
abstractions provide a migration point for future framework support; aggregate identity and business rules remain in
their owning domains. Shared application contracts also provide an injectable time source, with system and frozen clock
implementations in Infrastructure for production use and deterministic tests.

Shared also provides an outbox contract and an in-memory implementation. Command handlers receive the outbox through
constructor injection and append pending domain events after successful repository writes. Repositories persist aggregate
state and leave pending events available to the caller. The in-memory outbox keeps event objects only for its instance's
lifetime; durable messages, serialization, and asynchronous publication are not implemented.

Shared provides an application `TransactionManagerInterface` mapped to `PdoTransactionManager` through the reflection
resolver, using the configured shared `PDO` service. Wrap a command's
persistence and outbox operations in `transactional()` to commit them together or roll back on failure, using the same
exception-mode PDO connection for all participating adapters. Nested transactions are rejected. The callback must not
manage transactions or execute statements that implicitly commit. The in-memory outbox does not participate in database
transactions. `CreateTaskHandler` validates input before using the injected transaction manager to wrap task persistence and outbox event forwarding.

The current `Application` module assembles the API. The HTTP entry point is `public/v1/index.php`. Successful response
bodies use ExaPHP HATEOAS resources, and errors use Problem Details. Tests mirror production namespaces under each
module's `tests/` directory.

## Verification

With the services running, execute:

```sh
just validate
just nginx-test
just composer-audit --locked
just composer-dump-autoload
just php vendor/bin/phpunit module
```

The test suite includes MySQL repository integration tests using connection-local temporary tables created from the
module schema, and end-to-end HTTP tests that use the running nginx service. GitHub Actions
runs validation and tests, with dependency auditing in a separate job and on a weekly schedule.
