# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Task repository domain contract and an in-memory infrastructure implementation storing immutable state snapshots
  for the lifetime of the repository instance. `add()` rejects duplicate IDs with `TaskAlreadyExists`; `update()`
  rejects missing tasks with `TaskNotFound`. `remove()` permanently removes a task and rejects missing IDs; subsequent
  updates fail with `TaskNotFound`. Both exceptions expose the task ID and include it in their messages.
- Shared application clock contract with a UTC system clock and a frozen clock for deterministic tests.
- Shared module with domain event contracts and an aggregate base class, used by the Task aggregate and its events.
- Task aggregate collects immutable creation, rename, completion, reopening, and deletion events. Pulling events clears the
  collection; reconstitution, rejected actions, and renaming to the same title produce no events.
- `Task::delete()` records `TaskDeleted` for hard deletion through the repository without introducing a deleted status
  or preventing further actions on the in-memory aggregate.
- Task aggregate root supporting creation, renaming, completion with a supplied timestamp, reopening, and reconstitution
  of trusted persisted state through an immutable `TaskState` containing domain types, without reapplying domain
  validation. Aggregate properties are private and exposed through state snapshots. Completing an already completed task
  throws `TaskAlreadyCompleted`; reopening a task that is not completed throws `TaskNotCompleted`. Both exceptions
  expose the task ID and include it in their messages.
- Task status string-backed enum with pending, in-progress, and completed states.
- Task title value object with a 3–100 character creation limit, `InvalidTaskTitle` exceptions with distinct encoding
  and length messages, and unvalidated reconstitution of persisted titles.
- Task ID value object with UUID version 7 generation and validation using `ramsey/uuid`, throwing `InvalidTaskId`
  for invalid input.
- Application module with `/v1` HTTP endpoint.
- Initial project structure with an ExaPHP API, Docker development environment, and Just recipes.
