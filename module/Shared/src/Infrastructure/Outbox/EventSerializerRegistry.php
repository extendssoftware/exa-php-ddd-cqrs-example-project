<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox;

use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use InvalidArgumentException;
use JsonException;

final readonly class EventSerializerRegistry implements EventSerializerInterface
{
    /**
     * @param array<class-string<DomainEventInterface>, EventSerializerInterface> $serializers
     */
    public function __construct(private array $serializers) {}

    /**
     * @throws InvalidArgumentException When no serializer is registered for the exact event class or it rejects the event.
     * @throws JsonException When the selected serializer cannot encode the event payload.
     */
    public function serialize(DomainEventInterface $event): SerializedEvent
    {
        $serializer = $this->serializers[$event::class] ?? throw new InvalidArgumentException(
            'No serializer registered for event: ' . $event::class,
        );

        return $serializer->serialize($event);
    }
}
