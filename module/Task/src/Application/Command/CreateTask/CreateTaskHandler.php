<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask;

use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction\TransactionManagerInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use Throwable;

final readonly class CreateTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private OutboxInterface $outbox,
    ) {}

    /**
     * @throws InvalidTaskId When the supplied ID is not a valid UUID version 7.
     * @throws InvalidTaskTitle When the title is not valid UTF-8 or its length is outside the domain limits.
     * @throws TaskAlreadyExists When a task with the supplied ID is already stored.
     * @throws Throwable When transaction management, persistence, or outbox storage fails.
     */
    public function __invoke(CreateTask $command): void
    {
        $id = TaskId::fromString($command->taskId);
        $title = TaskTitle::fromString($command->title);

        $this->transactionManager->transactional(function () use ($id, $title): void {
            $task = Task::create($id, $title);
            $this->repository->add($task);

            foreach ($task->pullDomainEvents() as $event) {
                $this->outbox->append($event);
            }
        });
    }
}
