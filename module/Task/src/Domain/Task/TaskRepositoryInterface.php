<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task;

interface TaskRepositoryInterface
{
    public function find(TaskId $id): ?Task;

    public function save(Task $task): void;
}
