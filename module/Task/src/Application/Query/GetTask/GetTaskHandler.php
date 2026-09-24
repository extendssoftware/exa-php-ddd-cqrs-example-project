<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask;

use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;

final readonly class GetTaskHandler
{
    public function __construct(private TaskRepositoryInterface $repository) {}

    /**
     * @throws InvalidTaskId When the supplied ID is not a valid UUID version 7.
     * @throws TaskNotFound When no task with the supplied ID is stored.
     */
    public function __invoke(GetTask $query): GetTaskResult
    {
        $id = TaskId::fromString($query->taskId);
        $task = $this->repository->find($id);
        if ($task === null) {
            throw new TaskNotFound($id);
        }

        $state = $task->state();

        return new GetTaskResult(
            $state->id->value,
            $state->title->value,
            $state->status->value,
            $state->completedAt,
        );
    }
}
