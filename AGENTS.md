# AGENTS.md

## Project

- Target PHP 8.5 and keep `declare(strict_types=1);` in every PHP file.
- Follow the existing PSR-4 namespaces and module layout under `module/<Module>/`.
- Use native parameter, property, and return types; use PHPDoc only for information the type system cannot express.
- Prefer `final` classes unless inheritance is intentional.
- Prefer constructor injection over service location.
- Make value objects and other immutable classes `readonly`; use mutable state only when the domain requires it.
- Match the existing code style.
- Do not add tools or dependencies unless required by the change.

## DDD and CQRS

- Keep bounded contexts isolated. Integrate through explicit application contracts or events, not another context's internals.
- Keep `Domain` framework-agnostic: entities, value objects, domain services, domain events, and repository interfaces belong here.
- Put use cases, commands, queries, DTOs, and handlers in `Application`.
- Commands may change state; queries must be side-effect free.
- Put HTTP, persistence, messaging, ExaPHP integration, and repository implementations in `Infrastructure`.
- Dependencies point inward: Application may depend on Domain; Infrastructure may depend on Application and Domain; neither Domain nor Application may depend on Infrastructure.
- Enforce domain invariants in domain objects, not controllers or infrastructure.
- Avoid anemic entities, public mutable properties, service locators, and business logic in controllers.
- Keep controllers thin: validate and translate transport-level input, dispatch one command or query, and map its result to an HTTP response.
- Every HTTP response body, including error bodies, must be an ExaPHP HATEOAS resource; never expose raw arrays, entities, or DTOs.
- Status-only responses such as `204 No Content` may omit a body.
- Make transaction boundaries explicit around command handling; do not hide writes in query paths.

## Changes

- Make the smallest change that fully solves the task.
- Preserve existing public APIs unless the task explicitly requires changing them.
- Do not refactor unrelated code.
- Follow existing patterns before introducing a new abstraction or convention.
- Do not modify generated or vendor-managed files.

## Tests

- Add or update tests for every behavior change.
- A regression fix must include a test that reproduces the failure.
- Mirror production namespaces below `module/<Module>/tests/` and name test classes `*Test`.
- Unit-test domain rules and handlers without containers, HTTP, databases, networks, or the system clock.
- Use injected clock abstractions when behavior depends on time.
- Use integration tests for adapters and wiring.
- Test observable behavior instead of private implementation details.
- Use PHPUnit 13 attributes and data providers where they improve clarity.

## Verification

- Run the most focused relevant test first: `just php vendor/bin/phpunit path/to/ExampleTest.php`.
- Run the full suite before finishing: `just php vendor/bin/phpunit module`.
- Validate configuration after dependency or container changes: `just validate`.
- Keep Composer metadata and autoloading valid.
- Run `just composer dump-autoload` when autoload configuration requires regeneration.
- Do not finish with failing tests or new warnings or deprecations introduced by the change.
- Avoid unrelated formatting changes.
