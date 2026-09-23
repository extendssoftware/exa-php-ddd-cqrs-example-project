<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Repository;

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

    public function save(Task $task): void
    {
        $state = $task->state();
        $this->states[$state->id->value] = $state;
    }
}
