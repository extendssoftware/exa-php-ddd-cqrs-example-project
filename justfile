set positional-arguments := true

# List available recipes.
default:
    @just --list

# Build images, install dependencies, and start the project.
setup: docker-build composer-install docker-up

# Build Docker images.
docker-build:
    docker compose build

# Start the API at http://localhost:80.
docker-up:
    docker compose up -d

# Stop and remove containers without deleting volumes.
docker-down:
    docker compose down

# Restart running services.
docker-restart:
    docker compose restart

# Show container status.
docker-status:
    docker compose ps

# Follow logs, optionally for a service: just docker-logs php.
docker-logs *args:
    docker compose logs --follow "$@"

# Validate nginx configuration (requires a running nginx container).
nginx-test:
    docker compose exec -T nginx nginx -t

# Validate and gracefully reload nginx.
nginx-reload: nginx-test
    docker compose exec -T nginx nginx -s reload

# Install Composer dependencies without requiring running services.
composer-install:
    @just composer install --no-interaction

# Run Composer, e.g. just composer require vendor/package.
composer +args:
    @just docker-run-php composer "$@"

# Run PHP, e.g. just php --version.
php +args:
    @just docker-run-php php "$@"

# Run a command in a temporary PHP container.
docker-run-php +args:
    docker compose run --rm -T --no-deps php "$@"

# Validate Compose and Composer configuration.
validate:
    docker compose config --quiet
    @just composer validate --strict

# Open an interactive shell in a temporary PHP container.
docker-shell:
    docker compose run --rm --no-deps php sh
