<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\DeleteTask;

use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction\TransactionManagerInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use Throwable;

final readonly class DeleteTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private OutboxInterface $outbox,
    ) {}

    /**
     * @throws InvalidTaskId When the supplied ID is not a valid UUID version 7.
     * @throws TaskNotFound When the task does not exist or is removed before it can be persisted.
     * @throws Throwable When transaction management, persistence, or event forwarding fails.
     */
    public function __invoke(DeleteTask $command): void
    {
        $id = TaskId::fromString($command->taskId);

        $this->transactionManager->transactional(function () use ($id): void {
            $task = $this->repository->find($id);
            if ($task === null) {
                throw new TaskNotFound($id);
            }

            $task->delete();
            $this->repository->remove($task);

            foreach ($task->pullDomainEvents() as $event) {
                $this->outbox->append($event);
            }
        });
    }
}
