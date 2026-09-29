<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\ReopenTask;

use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction\TransactionManagerInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use Throwable;

final readonly class ReopenTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private OutboxInterface $outbox,
    ) {}

    /**
     * @throws InvalidTaskId When the supplied ID is not a valid UUID version 7.
     * @throws TaskNotFound When the task does not exist or is removed before it can be persisted.
     * @throws TaskNotCompleted When the task is not completed.
     * @throws Throwable When transaction management, persistence, or event forwarding fails.
     */
    public function __invoke(ReopenTask $command): void
    {
        $id = TaskId::fromString($command->taskId);

        $this->transactionManager->transactional(function () use ($id): void {
            $task = $this->repository->find($id);
            if ($task === null) {
                throw new TaskNotFound($id);
            }

            $task->reopen();
            $this->repository->update($task);

            foreach ($task->pullDomainEvents() as $event) {
                $this->outbox->append($event);
            }
        });
    }
}
