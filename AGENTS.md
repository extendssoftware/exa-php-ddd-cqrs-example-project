# AGENTS.md

## Project

- Target PHP 8.5 and keep `declare(strict_types=1);` in every PHP file.
- Follow the existing PSR-4 namespaces and module layout under `module/<Module>/`.
- Use native parameter, property, and return types; reserve PHPDoc for information the type system cannot express.
- Prefer small `final` classes and constructor injection. Make immutable classes `readonly`; use mutable state only when the domain requires it.
- Match the existing code style; do not add tools or dependencies unless the change needs them.

## DDD and CQRS

- Keep bounded contexts isolated. Integrate through explicit application contracts or events, not another context's internals.
- Keep `Domain` framework-agnostic: entities, value objects, domain services/events, and repository interfaces belong here.
- Put use cases, commands, queries, DTOs, and handlers in `Application`. Commands may change state; queries must be side-effect free.
- Put HTTP, persistence, messaging, ExaPHP integration, and repository implementations in `Infrastructure`.
- Depend inward: Application may depend on Domain, Infrastructure may depend on both, and neither Domain nor Application may depend on Infrastructure.
- Enforce invariants in domain objects. Avoid anemic entities, public mutable properties, service locators, and business logic in controllers.
- Keep controllers thin: validate and translate input, dispatch one command or query, and map its result to an HTTP response.
- Every HTTP response body, including an error body, must be an ExaPHP HATEOAS resource; never expose raw arrays, entities, or DTOs. Status-only responses such as `204 No Content` may omit a body.
- Make transaction boundaries explicit around command handling; do not hide writes in query paths.

## Tests

- Add or update tests for every behavior change; a regression fix must include a test that reproduces the failure.
- Mirror production namespaces below `module/<Module>/tests/` and name test classes `*Test`.
- Unit-test domain rules and handlers without containers, HTTP, databases, clocks, or networks.
- Use integration tests for adapters and wiring; test observable behavior instead of private implementation details.
- Use PHPUnit 13 attributes and data providers where they improve clarity.

## Verification

- Run a focused test first: `just php vendor/bin/phpunit path/to/ExampleTest.php`.
- Run the full suite before finishing: `just php vendor/bin/phpunit module`.
- Validate configuration after dependency or container changes: `just validate`.
- Keep Composer metadata and autoloading valid; run `just composer dump-autoload` after namespace or path changes.
- Do not finish with failing tests, warnings, deprecations, or unrelated formatting changes.
