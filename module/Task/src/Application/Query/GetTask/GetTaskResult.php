<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask;

use DateTimeImmutable;

final readonly class GetTaskResult
{
    public function __construct(
        public string $taskId,
        public string $title,
        public string $status,
        public ?DateTimeImmutable $completedAt,
    ) {}
}
