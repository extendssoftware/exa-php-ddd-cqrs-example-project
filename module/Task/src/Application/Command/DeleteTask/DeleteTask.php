<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\DeleteTask;

final readonly class DeleteTask
{
    public function __construct(public string $taskId) {}
}
