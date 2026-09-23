<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task;

use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;

interface TaskRepositoryInterface
{
    public function find(TaskId $id): ?Task;

    /**
     * @throws TaskAlreadyExists When a task with the same ID is already stored.
     */
    public function add(Task $task): void;

    /**
     * @throws TaskNotFound When no task with this ID is stored.
     */
    public function update(Task $task): void;

    /**
     * Permanently removes the stored task.
     *
     * @throws TaskNotFound When no task with this ID is stored.
     */
    public function remove(Task $task): void;
}
