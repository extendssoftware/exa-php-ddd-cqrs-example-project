# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- MySQL Docker service with persistent storage, a database readiness health check, and PHP PDO MySQL support.
  Database settings are shared with PHP through a local `.env` file and read by the PDO configuration from the environment.
- Configurable PDO connections through the application service locator, with a local MySQL configuration template.
- Task retrieval application use case through `GetTask` and `GetTaskHandler`, returning an immutable result DTO with
  the stored task ID, title, status, and completion time. Invalid IDs and missing tasks raise domain exceptions;
  queries perform no writes. HTTP exposure is not yet implemented.
- Task creation application use case through `CreateTask` and `CreateTaskHandler`, validating a caller-supplied ID and
  title and saving a pending task through the repository. The handler returns no value; HTTP exposure is not yet
  implemented.
- In-memory task storage and outbox demonstrating how task changes collect domain events for later processing. Data is
  not durable, and asynchronous event publication is not implemented.
- Task domain example demonstrating task creation, renaming, completion, reopening, and deletion events. Task operations
  are not yet exposed through the HTTP API.
- Problem Details error responses, including a fallback HTTP 500 response when application startup fails.
- Public `/v1` API information endpoint returning HAL JSON with a self-link.
- Initial ExaPHP DDD/CQRS example project for PHP 8.5, with a Docker-based development environment and `just setup` for
  installation and startup.

### Fixed

- Docker volume compatibility on hosts using SELinux.
