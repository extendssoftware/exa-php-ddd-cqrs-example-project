<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Domain\Task;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    /**
     * @return array<string, array{TaskStatus, ?DateTimeImmutable}>
     */
    public static function persistedStates(): array
    {
        return [
            'pending' => [TaskStatus::Pending, null],
            'in progress' => [TaskStatus::InProgress, null],
            'completed' => [TaskStatus::Completed, new DateTimeImmutable('2026-09-23T14:30:00+02:00')],
        ];
    }

    #[Test]
    #[DataProvider('persistedStates')]
    public function reconstituteRestoresPersistedStateAndHistoricalTitle(
        TaskStatus $status,
        ?DateTimeImmutable $completedAt,
    ): void {
        $id = TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
        $title = TaskTitle::reconstitute(str_repeat('a', 101));

        $task = Task::reconstitute($id, $title, $status, $completedAt);

        self::assertSame($id, $task->id);
        self::assertSame($title, $task->title);
        self::assertSame($status, $task->status);
        self::assertSame($completedAt, $task->completedAt);
    }

    /**
     * @return array<string, array{TaskStatus, ?DateTimeImmutable}>
     */
    public static function inconsistentStates(): array
    {
        $completedAt = new DateTimeImmutable('2026-09-23T12:00:00Z');

        return [
            'completed without time' => [TaskStatus::Completed, null],
            'pending with time' => [TaskStatus::Pending, $completedAt],
            'in progress with time' => [TaskStatus::InProgress, $completedAt],
        ];
    }

    #[Test]
    #[DataProvider('inconsistentStates')]
    public function reconstitutePreservesStateWithoutConsistencyValidation(
        TaskStatus $status,
        ?DateTimeImmutable $completedAt,
    ): void {
        $id = TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
        $title = TaskTitle::reconstitute('Existing task');

        $task = Task::reconstitute($id, $title, $status, $completedAt);

        self::assertSame($id, $task->id);
        self::assertSame($title, $task->title);
        self::assertSame($status, $task->status);
        self::assertSame($completedAt, $task->completedAt);
    }

    #[Test]
    public function createStartsPendingWithoutCompletionTime(): void
    {
        $id = TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
        $title = TaskTitle::fromString('Complete the task');

        $task = Task::create($id, $title);

        self::assertSame($id, $task->id);
        self::assertSame($title, $task->title);
        self::assertSame(TaskStatus::Pending, $task->status);
        self::assertNull($task->completedAt);
    }

    #[Test]
    public function renameChangesTitleAndPreservesIdentityAndStatus(): void
    {
        $task = $this->task();
        $id = $task->id;
        $title = TaskTitle::fromString('Updated task title');

        $task->rename($title);

        self::assertSame($title, $task->title);
        self::assertSame($id, $task->id);
        self::assertSame(TaskStatus::Pending, $task->status);
        self::assertNull($task->completedAt);
    }

    #[Test]
    public function completeRecordsSuppliedTimeAndPreservesIdentityAndTitle(): void
    {
        $task = $this->task();
        $id = $task->id;
        $title = $task->title;
        $completedAt = new DateTimeImmutable('2026-09-23T14:30:00+02:00');

        $task->complete($completedAt);

        self::assertSame(TaskStatus::Completed, $task->status);
        self::assertSame($completedAt, $task->completedAt);
        self::assertSame($id, $task->id);
        self::assertSame($title, $task->title);
    }

    #[Test]
    public function completeRejectsAlreadyCompletedTaskAndPreservesOriginalTime(): void
    {
        $task = $this->task();
        $completedAt = new DateTimeImmutable('2026-09-23T12:00:00Z');
        $task->complete($completedAt);

        $this->expectException(TaskAlreadyCompleted::class);
        $this->expectExceptionMessage('Task is already completed.');

        try {
            $task->complete(new DateTimeImmutable('2026-09-24T12:00:00Z'));
        } finally {
            self::assertSame(TaskStatus::Completed, $task->status);
            self::assertSame($completedAt, $task->completedAt);
        }
    }

    #[Test]
    public function renameCompletedTaskPreservesCompletion(): void
    {
        $task = $this->task();
        $completedAt = new DateTimeImmutable('2026-09-23T12:00:00Z');
        $task->complete($completedAt);
        $title = TaskTitle::fromString('Updated task title');

        $task->rename($title);

        self::assertSame($title, $task->title);
        self::assertSame(TaskStatus::Completed, $task->status);
        self::assertSame($completedAt, $task->completedAt);
    }

    #[Test]
    public function reopenReturnsCompletedTaskToPendingAndClearsCompletionTime(): void
    {
        $task = $this->task();
        $id = $task->id;
        $title = $task->title;
        $task->complete(new DateTimeImmutable('2026-09-23T12:00:00Z'));

        $task->reopen();

        self::assertSame(TaskStatus::Pending, $task->status);
        self::assertNull($task->completedAt);
        self::assertSame($id, $task->id);
        self::assertSame($title, $task->title);
    }

    /**
     * @return array<string, array{TaskStatus}>
     */
    public static function incompleteStatuses(): array
    {
        return [
            'pending' => [TaskStatus::Pending],
            'in progress' => [TaskStatus::InProgress],
        ];
    }

    #[Test]
    #[DataProvider('incompleteStatuses')]
    public function reopenRejectsIncompleteTaskAndPreservesState(TaskStatus $status): void
    {
        $id = TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
        $title = TaskTitle::fromString('Complete the task');
        $task = Task::reconstitute($id, $title, $status, null);

        $this->expectException(TaskNotCompleted::class);
        $this->expectExceptionMessage('Task is not completed.');

        try {
            $task->reopen();
        } finally {
            self::assertSame($id, $task->id);
            self::assertSame($title, $task->title);
            self::assertSame($status, $task->status);
            self::assertNull($task->completedAt);
        }
    }

    #[Test]
    public function completeAfterReopeningRecordsNewCompletionTime(): void
    {
        $task = $this->task();
        $task->complete(new DateTimeImmutable('2026-09-23T12:00:00Z'));
        $task->reopen();
        $completedAt = new DateTimeImmutable('2026-09-24T12:00:00Z');

        $task->complete($completedAt);

        self::assertSame(TaskStatus::Completed, $task->status);
        self::assertSame($completedAt, $task->completedAt);
    }

    private function task(): Task
    {
        return Task::create(
            TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced'),
            TaskTitle::fromString('Complete the task'),
        );
    }
}
