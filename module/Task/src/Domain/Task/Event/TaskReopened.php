<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event;

use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;

final readonly class TaskReopened implements DomainEventInterface
{
    public function __construct(private(set) TaskId $taskId) {}
}
