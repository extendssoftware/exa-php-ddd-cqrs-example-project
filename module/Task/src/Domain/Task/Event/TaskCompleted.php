<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;

final readonly class TaskCompleted implements DomainEventInterface
{
    public function __construct(
        private(set) TaskId $taskId,
        private(set) DateTimeImmutable $completedAt,
    ) {}
}
