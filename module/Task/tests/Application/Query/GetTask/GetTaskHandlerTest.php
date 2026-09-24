<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Query\GetTask;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask\GetTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask\GetTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCreated;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskState;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function str_repeat;

final class GetTaskHandlerTest extends TestCase
{
    private const string TASK_ID = '01902424-9b00-7cc3-98c4-2c1f7c675ced';

    /**
     * @return array<string, array{string, TaskStatus, ?DateTimeImmutable}>
     */
    public static function storedTasks(): array
    {
        return [
            'pending' => ['  Example task  ', TaskStatus::Pending, null],
            'in progress' => ['Example task', TaskStatus::InProgress, null],
            'completed' => [
                'Example task',
                TaskStatus::Completed,
                new DateTimeImmutable('2026-09-23T14:30:00.123456+02:00'),
            ],
            'historical title' => [str_repeat('a', 101), TaskStatus::Pending, null],
        ];
    }

    #[Test]
    #[DataProvider('storedTasks')]
    public function invokeReturnsStoredTaskWithoutChangingIt(
        string $title,
        TaskStatus $status,
        ?DateTimeImmutable $completedAt,
    ): void {
        $id = TaskId::fromString(self::TASK_ID);
        $state = new TaskState($id, TaskTitle::reconstitute($title), $status, $completedAt);
        $task = Task::reconstitute($state);
        $repository = $this->readOnlyRepository();
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(self::equalTo($id))
            ->willReturn($task);
        $handler = new GetTaskHandler($repository);

        $result = $handler(new GetTask(self::TASK_ID));

        self::assertSame(self::TASK_ID, $result->taskId);
        self::assertSame($title, $result->title);
        self::assertSame($status->value, $result->status);
        self::assertSame($completedAt, $result->completedAt);
        self::assertEquals($state, $task->state());
        self::assertSame([], $task->pullDomainEvents());
    }

    #[Test]
    public function invokePreservesPendingDomainEvents(): void
    {
        $id = TaskId::fromString(self::TASK_ID);
        $title = TaskTitle::fromString('Example task');
        $task = Task::create($id, $title);
        $repository = $this->readOnlyRepository();
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(self::equalTo($id))
            ->willReturn($task);
        $handler = new GetTaskHandler($repository);

        $handler(new GetTask(self::TASK_ID));

        self::assertEquals([new TaskCreated($id, $title)], $task->pullDomainEvents());
    }

    #[Test]
    public function invokeRejectsMissingTask(): void
    {
        $id = TaskId::fromString(self::TASK_ID);
        $repository = $this->readOnlyRepository();
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(self::equalTo($id))
            ->willReturn(null);
        $handler = new GetTaskHandler($repository);

        $this->expectException(TaskNotFound::class);

        try {
            $handler(new GetTask(self::TASK_ID));
        } catch (TaskNotFound $exception) {
            self::assertEquals($id, $exception->taskId);

            throw $exception;
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIds(): array
    {
        return [
            'malformed' => ['invalid'],
            'wrong UUID version' => ['550e8400-e29b-41d4-a716-446655440000'],
        ];
    }

    #[Test]
    #[DataProvider('invalidIds')]
    public function invokeRejectsInvalidIdWithoutReading(string $taskId): void
    {
        $repository = $this->readOnlyRepository();
        $repository
            ->expects(self::never())
            ->method('find');
        $handler = new GetTaskHandler($repository);

        $this->expectException(InvalidTaskId::class);

        $handler(new GetTask($taskId));
    }

    private function readOnlyRepository(): TaskRepositoryInterface&MockObject
    {
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository
            ->expects(self::never())
            ->method('add');
        $repository
            ->expects(self::never())
            ->method('update');
        $repository
            ->expects(self::never())
            ->method('remove');

        return $repository;
    }
}
