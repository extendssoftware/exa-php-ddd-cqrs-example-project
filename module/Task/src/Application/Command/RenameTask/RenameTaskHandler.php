<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\RenameTask;

use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction\TransactionManagerInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use Throwable;

final readonly class RenameTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private OutboxInterface $outbox,
    ) {}

    /**
     * @throws InvalidTaskId When the supplied ID is not a valid UUID version 7.
     * @throws TaskNotFound When the task does not exist or is removed before it can be persisted.
     * @throws InvalidTaskTitle When the supplied title violates the domain validation rules.
     * @throws Throwable When transaction management, persistence, or event forwarding fails.
     */
    public function __invoke(RenameTask $command): void
    {
        $id = TaskId::fromString($command->taskId);
        $title = TaskTitle::fromString($command->title);

        $this->transactionManager->transactional(function () use ($id, $title): void {
            $task = $this->repository->find($id);
            if ($task === null) {
                throw new TaskNotFound($id);
            }

            $task->rename($title);
            $this->repository->update($task);

            foreach ($task->pullDomainEvents() as $event) {
                $this->outbox->append($event);
            }
        });
    }
}
