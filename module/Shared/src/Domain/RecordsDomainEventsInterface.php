<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Domain;

interface RecordsDomainEventsInterface
{
    /**
     * Returns and clears the recorded events in recording order.
     *
     * @return list<DomainEventInterface>
     */
    public function pullDomainEvents(): array;
}
