<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask;

use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;

final readonly class CreateTaskHandler
{
    public function __construct(private TaskRepositoryInterface $repository) {}

    /**
     * @throws InvalidTaskId When the supplied ID is not a valid UUID version 7.
     * @throws InvalidTaskTitle When the title is not valid UTF-8 or its length is outside the domain limits.
     * @throws TaskAlreadyExists When a task with the supplied ID is already stored.
     */
    public function __invoke(CreateTask $command): void
    {
        $id = TaskId::fromString($command->taskId);
        $title = TaskTitle::fromString($command->title);

        $this->repository->add(Task::create($id, $title));
    }
}
