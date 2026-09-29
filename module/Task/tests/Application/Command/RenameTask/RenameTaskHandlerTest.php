<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Command\RenameTask;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction\TransactionManagerInterface;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\RenameTask\RenameTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\RenameTask\RenameTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskRenamed;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskState;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RenameTaskHandlerTest extends TestCase
{
    private const string ID = '01902424-9b00-7cc3-98c4-2c1f7c675ced';

    private TaskRepositoryInterface&MockObject $repository;
    private TransactionManagerInterface&MockObject $transactionManager;
    private OutboxInterface&MockObject $outbox;
    private RenameTaskHandler $handler;
    private bool $insideTransaction = false;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TaskRepositoryInterface::class);
        $this->repository->expects(self::never())->method('add');
        $this->transactionManager = $this->createMock(TransactionManagerInterface::class);
        $this->outbox = $this->createMock(OutboxInterface::class);
        $this->handler = new RenameTaskHandler($this->repository, $this->transactionManager, $this->outbox);
    }

    #[Test]
    public function invokeLoadsChangesPersistsAndForwardsEventInsideOneTransaction(): void
    {
        $task = $this->task();
        $saved = false;
        $this->transaction();
        $this->repository->expects(self::once())->method('find')->with(self::equalTo($task->state()->id))
            ->willReturnCallback(function () use ($task): Task {
                self::assertTrue($this->insideTransaction);
                return $task;
            });
        $this->repository->expects(self::once())->method('update')->with(self::identicalTo($task))
            ->willReturnCallback(function (Task $stored) use (&$saved): void {
                self::assertTrue($this->insideTransaction);
                self::assertSame('Renamed task', $stored->state()->title->value);
                $saved = true;
            });
        $this->outbox->expects(self::once())->method('append')
            ->with(self::equalTo(new TaskRenamed($task->state()->id, TaskTitle::fromString('Renamed task'))))
            ->willReturnCallback(function () use (&$saved): void {
                self::assertTrue($saved);
                self::assertTrue($this->insideTransaction);
            });

        ($this->handler)($this->command());

        self::assertSame([], $task->pullDomainEvents());
        self::assertFalse($this->insideTransaction);
    }

    #[Test]
    public function invokeRejectsMissingTaskWithoutWriting(): void
    {
        $this->transaction();
        $this->repository->expects(self::once())->method('find')->willReturn(null);
        $this->repository->expects(self::never())->method('update');
        $this->outbox->expects(self::never())->method('append');
        $this->expectException(TaskNotFound::class);

        ($this->handler)($this->command());
    }

    #[Test]
    public function invokeRejectsInvalidIdBeforeStartingTransaction(): void
    {
        $this->transactionManager->expects(self::never())->method('transactional');
        $this->repository->expects(self::never())->method('find');
        $this->repository->expects(self::never())->method('update');
        $this->outbox->expects(self::never())->method('append');
        $this->expectException(InvalidTaskId::class);

        ($this->handler)(new RenameTask('invalid', 'Renamed task'));
    }

    #[Test]
    public function invokePropagatesPersistenceFailureWithoutForwardingEvents(): void
    {
        $task = $this->task();
        $failure = new RuntimeException('Persistence failed');
        $this->transaction();
        $this->repository->expects(self::once())->method('find')->willReturn($task);
        $this->repository->expects(self::once())->method('update')->willThrowException($failure);
        $this->outbox->expects(self::never())->method('append');
        $this->expectExceptionObject($failure);

        try {
            ($this->handler)($this->command());
        } finally {
            self::assertEquals([new TaskRenamed($task->state()->id, TaskTitle::fromString('Renamed task'))], $task->pullDomainEvents());
        }
    }

    #[Test]
    public function invokePropagatesOutboxFailureThroughTransaction(): void
    {
        $task = $this->task();
        $failure = new RuntimeException('Outbox failed');
        $this->transaction();
        $this->repository->expects(self::once())->method('find')->willReturn($task);
        $this->repository->expects(self::once())->method('update')->with(self::identicalTo($task));
        $this->outbox->expects(self::once())->method('append')->willThrowException($failure);
        $this->expectExceptionObject($failure);

        ($this->handler)($this->command());
    }

    #[Test]
    public function invokePropagatesTransactionFailureWithoutLoadingTask(): void
    {
        $failure = new RuntimeException('Transaction failed');
        $this->transactionManager->expects(self::once())->method('transactional')->willThrowException($failure);
        $this->repository->expects(self::never())->method('find');
        $this->repository->expects(self::never())->method('update');
        $this->outbox->expects(self::never())->method('append');
        $this->expectExceptionObject($failure);

        ($this->handler)($this->command());
    }

    #[Test]
    public function invokeRejectsInvalidTitleBeforeStartingTransaction(): void
    {
        $this->transactionManager->expects(self::never())->method('transactional');
        $this->repository->expects(self::never())->method('find');
        $this->repository->expects(self::never())->method('update');
        $this->outbox->expects(self::never())->method('append');
        $this->expectException(InvalidTaskTitle::class);

        ($this->handler)(new RenameTask(self::ID, 'ab'));
    }

    #[Test]
    public function invokeWithUnchangedTitleDoesNotAppendEvent(): void
    {
        $task = $this->task();
        $this->transaction();
        $this->repository->expects(self::once())->method('find')->willReturn($task);
        $this->repository->expects(self::once())->method('update')->with(self::identicalTo($task));
        $this->outbox->expects(self::never())->method('append');

        ($this->handler)(new RenameTask(self::ID, 'Original task'));
    }

    private function transaction(): void
    {
        $this->transactionManager->expects(self::once())->method('transactional')->willReturnCallback(
            function (callable $operation): mixed {
                $this->insideTransaction = true;
                try {
                    return $operation();
                } finally {
                    $this->insideTransaction = false;
                }
            },
        );
    }

    private function task(TaskStatus $status = TaskStatus::Open): Task
    {
        return Task::reconstitute(new TaskState(
            TaskId::fromString(self::ID),
            TaskTitle::fromString('Original task'),
            $status,
            new DateTimeImmutable('2020-01-01T00:00:00Z'),
            $status === TaskStatus::Completed ? $this->time() : null,
        ));
    }

    private function command(): RenameTask
    {
        return new RenameTask(self::ID, 'Renamed task');
    }

    private function time(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00');
    }
}
