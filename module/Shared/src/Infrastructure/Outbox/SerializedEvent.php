<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox;

final readonly class SerializedEvent
{
    public function __construct(
        public string $type,
        public int $version,
        public string $payload,
    ) {}
}
