<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Repository;

use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskState;

final class InMemoryTaskRepository implements TaskRepositoryInterface
{
    /**
     * @var array<string, TaskState>
     */
    private array $states = [];

    public function find(TaskId $id): ?Task
    {
        $state = $this->states[$id->value] ?? null;

        return $state === null ? null : Task::reconstitute($state);
    }

    /**
     * @throws TaskAlreadyExists When a task with the same ID is already stored.
     */
    public function add(Task $task): void
    {
        $state = $task->state();
        if (isset($this->states[$state->id->value])) {
            throw new TaskAlreadyExists($state->id);
        }

        $this->states[$state->id->value] = $state;
    }

    /**
     * @throws TaskNotFound When no task with this ID is stored.
     */
    public function update(Task $task): void
    {
        $state = $task->state();
        if (!isset($this->states[$state->id->value])) {
            throw new TaskNotFound($state->id);
        }

        $this->states[$state->id->value] = $state;
    }

    /**
     * @throws TaskNotFound When no task with this ID is stored.
     */
    public function remove(TaskId $id): void
    {
        if (!isset($this->states[$id->value])) {
            throw new TaskNotFound($id);
        }

        unset($this->states[$id->value]);
    }
}
