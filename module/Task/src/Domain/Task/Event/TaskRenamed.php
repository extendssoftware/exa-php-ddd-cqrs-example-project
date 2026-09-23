<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event;

use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;

final readonly class TaskRenamed implements DomainEventInterface
{
    public function __construct(
        private(set) TaskId $taskId,
        private(set) TaskTitle $title,
    ) {}
}
