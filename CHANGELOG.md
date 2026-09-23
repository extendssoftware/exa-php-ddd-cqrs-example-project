# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Task aggregate root supporting creation, renaming, completion with a supplied timestamp, reopening, and reconstitution
  of trusted persisted state without reapplying domain validation. Completing an already completed task throws
  `TaskAlreadyCompleted`; reopening a task that is not completed throws `TaskNotCompleted`.
- Task status string-backed enum with pending, in-progress, and completed states.
- Task title value object with a 3–100 character creation limit and unvalidated reconstitution of persisted titles.
- Task ID value object with UUID version 7 generation and validation using `ramsey/uuid`.
- Application module with `/v1` HTTP endpoint.
- Initial project structure with an ExaPHP API, Docker development environment, and Just recipes.
