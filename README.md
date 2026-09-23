# ExaPHP DDD/CQRS Example Project

An example PHP application built with ExaPHP to demonstrate Domain-Driven Design (DDD) and Command Query Responsibility
Segregation (CQRS).

Development is coordinated by a developer and supported by AI through instructions and prompts. The developer sets the
direction, defines requirements, and guides architectural decisions. AI assists with implementation, tests, and
documentation using task-specific prompts and the project conventions in [AGENTS.md](AGENTS.md).

This project is a work in progress. It currently provides the application bootstrap, a public API information endpoint,
authorization for its self-link, and automated tests. A complete DDD/CQRS use case and database integration are still
planned; see [TODO.md](TODO.md).

## Getting started

Install Docker with Docker Compose and the `just` command runner. PHP 8.5, Composer, and nginx run in containers; local
PHP and Composer installations are not required.

From the project root, run:

```sh
just setup
```

This builds the images, installs dependencies, and starts the services. Open [http://localhost/v1](http://localhost/v1)
to view the API information as HAL JSON. Port 80 must be available on the host.

To stop the services:

```sh
just docker-down
```

Run `just` to list the available recipes.

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
contracts and an optional aggregate base class for event collection, with no runtime module registration. These local
abstractions provide a migration point for future framework support; aggregate identity and business rules remain in
their owning domains. Shared application contracts also provide an injectable time source, with system and frozen clock
implementations in Infrastructure for production use and deterministic tests.

Shared also provides an outbox contract and an in-memory implementation. The in-memory task repository receives the
outbox through constructor injection and appends pending events after successful writes. This dummy implementation keeps
event objects only for its instance's lifetime; durable messages, serialization, atomic database transactions, and
asynchronous publication are not implemented.

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

The test suite includes integration tests and end-to-end HTTP tests that use the running nginx service. GitHub Actions
runs validation and tests, with dependency auditing in a separate job and on a weekly schedule.
