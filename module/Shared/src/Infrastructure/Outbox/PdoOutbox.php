<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox;

use DateTimeZone;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Clock\ClockInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use InvalidArgumentException;
use JsonException;
use PDO;
use PDOException;
use Ramsey\Uuid\Uuid;

final readonly class PdoOutbox implements OutboxInterface
{
    public function __construct(
        private PDO $pdo,
        private EventSerializerInterface $serializer,
        private ClockInterface $clock,
    ) {}

    /**
     * @throws InvalidArgumentException When the event type is unsupported.
     * @throws JsonException When the event payload cannot be encoded as JSON.
     * @throws PDOException When storing the message fails.
     */
    public function append(DomainEventInterface $event): void
    {
        $message = $this->serializer->serialize($event);

        $statement = $this->pdo->prepare(
            'INSERT INTO outbox (id, event_type, event_version, payload, recorded_at) VALUES (UUID_TO_BIN(:id), :event_type, :event_version, :payload, :recorded_at)',
        );
        $statement->execute([
            'id' => Uuid::uuid7()
                        ->toString(),
            'event_type' => $message->type,
            'event_version' => $message->version,
            'payload' => $message->payload,
            'recorded_at' => $this->clock
                ->now()
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s.u'),
        ]);
    }
}
