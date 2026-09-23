<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox;

use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;

interface OutboxInterface
{
    public function append(DomainEventInterface $event): void;
}
