<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\CompleteTask;

use ExtendsSoftware\ExaPHPExample\Shared\Application\Clock\ClockInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction\TransactionManagerInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use Throwable;

final readonly class CompleteTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private OutboxInterface $outbox,
        private ClockInterface $clock,
    ) {}

    /**
     * @throws InvalidTaskId When the supplied ID is not a valid UUID version 7.
     * @throws TaskNotFound When the task does not exist or is removed before it can be persisted.
     * @throws TaskAlreadyCompleted When the task is already completed.
     * @throws Throwable When transaction management, persistence, event forwarding, or reading the clock fails.
     */
    public function __invoke(CompleteTask $command): void
    {
        $id = TaskId::fromString($command->taskId);

        $this->transactionManager->transactional(function () use ($id): void {
            $task = $this->repository->find($id);
            if ($task === null) {
                throw new TaskNotFound($id);
            }

            $task->complete($this->clock->now());
            $this->repository->update($task);

            foreach ($task->pullDomainEvents() as $event) {
                $this->outbox->append($event);
            }
        });
    }
}
