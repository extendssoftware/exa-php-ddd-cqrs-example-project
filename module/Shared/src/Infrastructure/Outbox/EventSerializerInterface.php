<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox;

use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use InvalidArgumentException;
use JsonException;

interface EventSerializerInterface
{
    /**
     * @throws InvalidArgumentException When the event type is unsupported.
     * @throws JsonException When the event payload cannot be encoded as JSON.
     */
    public function serialize(DomainEventInterface $event): SerializedEvent;
}
