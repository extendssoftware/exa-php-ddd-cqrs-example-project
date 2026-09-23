<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Domain;

abstract class AbstractAggregateRoot implements RecordsDomainEventsInterface
{
    /**
     * @var list<DomainEventInterface>
     */
    private array $domainEvents = [];

    final protected function recordThat(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return list<DomainEventInterface>
     */
    final public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
