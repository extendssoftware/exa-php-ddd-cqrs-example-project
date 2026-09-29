<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\CompleteTask;

final readonly class CompleteTask
{
    public function __construct(public string $taskId) {}
}
