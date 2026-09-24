# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Initial ExaPHP DDD/CQRS example project for PHP 8.5, with a Docker-based development environment and `just setup` for
  installation and startup.
- Public `/v1` API information endpoint returning HAL JSON with a self-link.
- Problem Details error responses, including a fallback HTTP 500 response when application startup fails.
- Task domain example demonstrating task creation, renaming, completion, reopening, and deletion events. Task operations
  are not yet exposed through the HTTP API.
- In-memory task storage and outbox demonstrating how task changes collect domain events for later processing. Data is
  not durable, and asynchronous event publication is not implemented.
- Task creation application use case through `CreateTask` and `CreateTaskHandler`, validating a caller-supplied ID and
  title and saving a pending task through the repository. The handler returns no value; HTTP exposure is not yet
  implemented.

### Fixed

- Docker volume compatibility on hosts using SELinux.
