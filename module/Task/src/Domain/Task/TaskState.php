<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task;

use DateTimeImmutable;

final readonly class TaskState
{
    public function __construct(
        public TaskId $id,
        public TaskTitle $title,
        public TaskStatus $status,
        public ?DateTimeImmutable $completedAt,
    ) {}
}
