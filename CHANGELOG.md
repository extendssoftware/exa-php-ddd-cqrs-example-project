# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

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
