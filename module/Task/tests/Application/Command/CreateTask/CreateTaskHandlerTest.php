<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Command\CreateTask;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Clock\ClockInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction\TransactionManagerInterface;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCreated;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function str_repeat;

final class CreateTaskHandlerTest extends TestCase
{
    private const string TASK_ID = '01902424-9b00-7cc3-98c4-2c1f7c675ced';

    #[Test]
    public function invokeAddsOpenTaskWithSuppliedIdAndTitle(): void
    {
        $insideTransaction = false;
        $saved = null;
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('add')
            ->with(
                self::callback(
                    static function (Task $task) use (&$insideTransaction, &$saved): bool {
                        $saved = $task;
                        self::assertTrue($insideTransaction);
                        $state = $task->state();
                        self::assertSame(self::TASK_ID, $state->id->value);
                        self::assertSame('  Example task  ', $state->title->value);
                        self::assertSame(TaskStatus::Open, $state->status);
                        self::assertNull($state->completedAt);
                        self::assertEquals(new DateTimeImmutable('2026-09-23T10:00:00.123456+02:00'), $state->createdAt);

                        return true;
                    },
                ),
            );
        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $transactionManager->expects(self::once())->method('transactional')->willReturnCallback(
            static function (callable $operation) use (&$insideTransaction): mixed {
                $insideTransaction = true;
                try {
                    return $operation();
                } finally {
                    $insideTransaction = false;
                }
            },
        );
        $outbox = $this->createMock(OutboxInterface::class);
        $outbox->expects(self::once())->method('append')->willReturnCallback(
            static function (TaskCreated $event) use (&$insideTransaction, &$saved): void {
                self::assertTrue($insideTransaction);
                self::assertInstanceOf(Task::class, $saved);
                self::assertEquals(new TaskCreated(
                    TaskId::fromString(self::TASK_ID),
                    TaskTitle::fromString('  Example task  '),
                    new DateTimeImmutable('2026-09-23T10:00:00.123456+02:00'),
                ), $event);
            },
        );
        $handler = new CreateTaskHandler($repository, $transactionManager, $outbox, $this->clock());

        $handler(new CreateTask(self::TASK_ID, '  Example task  '));
        self::assertSame([], $saved->pullDomainEvents());
    }

    /**
     * @return array<string, array{string, string, class-string<InvalidTaskId|InvalidTaskTitle>}>
     */
    public static function invalidCommands(): array
    {
        return [
            'malformed ID' => ['invalid', 'Example task', InvalidTaskId::class],
            'wrong UUID version' => ['550e8400-e29b-41d4-a716-446655440000', 'Example task', InvalidTaskId::class],
            'short title' => [self::TASK_ID, 'ab', InvalidTaskTitle::class],
            'long title' => [self::TASK_ID, str_repeat('a', 101), InvalidTaskTitle::class],
            'invalid title encoding' => [self::TASK_ID, "abc\xFF", InvalidTaskTitle::class],
        ];
    }

    /**
     * @param class-string<InvalidTaskId|InvalidTaskTitle> $exception
     */
    #[Test]
    #[DataProvider('invalidCommands')]
    public function invokeRejectsInvalidInputWithoutWriting(string $taskId, string $title, string $exception): void
    {
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository
            ->expects(self::never())
            ->method('add');
        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $transactionManager->expects(self::never())->method('transactional');
        $outbox = $this->createMock(OutboxInterface::class);
        $outbox->expects(self::never())->method('append');
        $handler = new CreateTaskHandler($repository, $transactionManager, $outbox, $this->clock());

        $this->expectException($exception);

        $handler(new CreateTask($taskId, $title));
    }

    #[Test]
    public function invokePropagatesRepositoryFailure(): void
    {
        $exception = new TaskAlreadyExists(TaskId::fromString(self::TASK_ID));
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('add')
            ->willThrowException($exception);
        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $transactionManager->expects(self::once())->method('transactional')->willReturnCallback(
            static function (callable $operation) use ($exception): mixed {
                try {
                    return $operation();
                } catch (TaskAlreadyExists $caught) {
                    self::assertSame($exception, $caught);
                    throw $caught;
                }
            },
        );
        $outbox = $this->createMock(OutboxInterface::class);
        $outbox->expects(self::never())->method('append');
        $handler = new CreateTaskHandler($repository, $transactionManager, $outbox, $this->clock());

        $this->expectExceptionObject($exception);

        $handler(new CreateTask(self::TASK_ID, 'Example task'));
    }

    #[Test]
    public function invokePropagatesTransactionFailureWithoutWriting(): void
    {
        $exception = new RuntimeException('Could not start transaction');
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository->expects(self::never())->method('add');
        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $transactionManager->expects(self::once())->method('transactional')->willThrowException($exception);
        $outbox = $this->createMock(OutboxInterface::class);
        $outbox->expects(self::never())->method('append');
        $handler = new CreateTaskHandler($repository, $transactionManager, $outbox, $this->clock());

        $this->expectExceptionObject($exception);

        $handler(new CreateTask(self::TASK_ID, 'Example task'));
    }

    #[Test]
    public function invokePropagatesOutboxFailureThroughTransactionManager(): void
    {
        $exception = new RuntimeException('Outbox unavailable');
        $saved = false;
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository->expects(self::once())->method('add')->willReturnCallback(
            static function (Task $task) use (&$saved): void {
                $saved = true;
            },
        );
        $outbox = $this->createMock(OutboxInterface::class);
        $outbox->expects(self::once())->method('append')->willReturnCallback(
            static function (TaskCreated $event) use (&$saved, $exception): never {
                self::assertTrue($saved);
                throw $exception;
            },
        );
        $transactionManager = $this->createMock(TransactionManagerInterface::class);
        $transactionManager->expects(self::once())->method('transactional')->willReturnCallback(
            static function (callable $operation) use ($exception): void {
                try {
                    $operation();
                    self::fail('Outbox failure must reach the transaction manager.');
                } catch (RuntimeException $caught) {
                    self::assertSame($exception, $caught);
                    throw $caught;
                }
            },
        );
        $handler = new CreateTaskHandler($repository, $transactionManager, $outbox, $this->clock());

        $this->expectExceptionObject($exception);

        $handler(new CreateTask(self::TASK_ID, 'Example task'));
    }

    private function clock(): ClockInterface
    {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-09-23T10:00:00.123456+02:00'));

        return $clock;
    }
}
