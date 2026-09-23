<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox;

use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;

final class InMemoryOutbox implements OutboxInterface
{
    /**
     * @var list<DomainEventInterface>
     */
    private array $events = [];

    public function append(DomainEventInterface $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<DomainEventInterface>
     */
    public function all(): array
    {
        return $this->events;
    }
}
