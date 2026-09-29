<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\ReopenTask;

final readonly class ReopenTask
{
    public function __construct(public string $taskId) {}
}
