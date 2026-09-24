<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask;

final readonly class CreateTask
{
    public function __construct(
        public string $taskId,
        public string $title,
    ) {}
}
