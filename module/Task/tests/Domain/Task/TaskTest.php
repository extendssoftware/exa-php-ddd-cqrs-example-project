<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Domain\Task;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCreated;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskRenamed;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskReopened;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskState;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

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

        $task = Task::reconstitute(new TaskState($id, $title, $status, $completedAt));

        self::assertSame($id, $task->state()->id);
        self::assertSame($title, $task->state()->title);
        self::assertSame($status, $task->state()->status);
        self::assertSame($completedAt, $task->state()->completedAt);
        self::assertSame([], $task->pullDomainEvents());
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

        $task = Task::reconstitute(new TaskState($id, $title, $status, $completedAt));

        self::assertSame($id, $task->state()->id);
        self::assertSame($title, $task->state()->title);
        self::assertSame($status, $task->state()->status);
        self::assertSame($completedAt, $task->state()->completedAt);
        self::assertSame([], $task->pullDomainEvents());
    }

    #[Test]
    public function createStartsPendingWithoutCompletionTime(): void
    {
        $id = TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
        $title = TaskTitle::fromString('Complete the task');

        $task = Task::create($id, $title);

        self::assertSame($id, $task->state()->id);
        self::assertSame($title, $task->state()->title);
        self::assertSame(TaskStatus::Pending, $task->state()->status);
        self::assertNull($task->state()->completedAt);
    }

    #[Test]
    public function renameChangesTitleAndPreservesIdentityAndStatus(): void
    {
        $task = $this->task();
        $id = $task->state()->id;
        $title = TaskTitle::fromString('Updated task title');

        $task->rename($title);

        self::assertSame($title, $task->state()->title);
        self::assertSame($id, $task->state()->id);
        self::assertSame(TaskStatus::Pending, $task->state()->status);
        self::assertNull($task->state()->completedAt);
    }

    #[Test]
    public function completeRecordsSuppliedTimeAndPreservesIdentityAndTitle(): void
    {
        $task = $this->task();
        $id = $task->state()->id;
        $title = $task->state()->title;
        $completedAt = new DateTimeImmutable('2026-09-23T14:30:00+02:00');

        $task->complete($completedAt);

        self::assertSame(TaskStatus::Completed, $task->state()->status);
        self::assertSame($completedAt, $task->state()->completedAt);
        self::assertSame($id, $task->state()->id);
        self::assertSame($title, $task->state()->title);
    }

    #[Test]
    public function completeRejectsAlreadyCompletedTaskAndPreservesOriginalTime(): void
    {
        $task = $this->task();
        $completedAt = new DateTimeImmutable('2026-09-23T12:00:00Z');
        $task->complete($completedAt);

        $this->expectException(TaskAlreadyCompleted::class);
        $this->expectExceptionMessageIsOrContains('Task is already completed.');

        try {
            $task->complete(new DateTimeImmutable('2026-09-24T12:00:00Z'));
        } finally {
            self::assertSame(TaskStatus::Completed, $task->state()->status);
            self::assertSame($completedAt, $task->state()->completedAt);
            self::assertEquals([
                new TaskCreated($task->state()->id, $task->state()->title),
                new TaskCompleted($task->state()->id, $completedAt),
            ], $task->pullDomainEvents());
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

        self::assertSame($title, $task->state()->title);
        self::assertSame(TaskStatus::Completed, $task->state()->status);
        self::assertSame($completedAt, $task->state()->completedAt);
    }

    #[Test]
    public function reopenReturnsCompletedTaskToPendingAndClearsCompletionTime(): void
    {
        $task = $this->task();
        $id = $task->state()->id;
        $title = $task->state()->title;
        $task->complete(new DateTimeImmutable('2026-09-23T12:00:00Z'));

        $task->reopen();

        self::assertSame(TaskStatus::Pending, $task->state()->status);
        self::assertNull($task->state()->completedAt);
        self::assertSame($id, $task->state()->id);
        self::assertSame($title, $task->state()->title);
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
        $task = Task::reconstitute(new TaskState($id, $title, $status, null));

        $this->expectException(TaskNotCompleted::class);
        $this->expectExceptionMessageIsOrContains('Task is not completed.');

        try {
            $task->reopen();
        } finally {
            self::assertSame($id, $task->state()->id);
            self::assertSame($title, $task->state()->title);
            self::assertSame($status, $task->state()->status);
            self::assertNull($task->state()->completedAt);
            self::assertSame([], $task->pullDomainEvents());
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

        self::assertSame(TaskStatus::Completed, $task->state()->status);
        self::assertSame($completedAt, $task->state()->completedAt);
    }

    #[Test]
    public function pullDomainEventsReturnsActionSnapshotsInOrderAndClearsCollection(): void
    {
        $task = $this->task();
        $originalTitle = $task->state()->title;
        $renamedTitle = TaskTitle::fromString('Renamed task');
        $completedAt = new DateTimeImmutable('2026-09-23T14:30:00+02:00');

        $task->rename($renamedTitle);
        $task->complete($completedAt);
        $task->reopen();

        $events = $task->pullDomainEvents();

        self::assertEquals([
            new TaskCreated($task->state()->id, $originalTitle),
            new TaskRenamed($task->state()->id, $renamedTitle),
            new TaskCompleted($task->state()->id, $completedAt),
            new TaskReopened($task->state()->id),
        ], $events);
        self::assertSame([], $task->pullDomainEvents());

        $nextTitle = TaskTitle::fromString('Another title');
        $task->rename($nextTitle);

        self::assertEquals([new TaskRenamed($task->state()->id, $nextTitle)], $task->pullDomainEvents());
        self::assertSame($originalTitle, $events[0]->title);
        self::assertSame($renamedTitle, $events[1]->title);
        self::assertSame($completedAt, $events[2]->completedAt);
    }

    #[Test]
    public function renameToSameTitleDoesNotRecordAnEvent(): void
    {
        $task = $this->task();
        $title = $task->state()->title;
        $task->pullDomainEvents();

        $task->rename(TaskTitle::fromString($title->value));

        self::assertSame($title, $task->state()->title);
        self::assertSame([], $task->pullDomainEvents());
    }

    #[Test]
    public function reconstitutedTaskRecordsOnlyNewActions(): void
    {
        $id = TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
        $task = Task::reconstitute(new TaskState(
            $id,
            TaskTitle::reconstitute('Existing title'),
            TaskStatus::Completed,
            new DateTimeImmutable('2026-09-23T12:00:00Z'),
        ));

        $task->reopen();

        self::assertEquals([new TaskReopened($id)], $task->pullDomainEvents());
    }

    #[Test]
    public function domainEventsBelongToTheirAggregate(): void
    {
        $first = $this->task();
        $second = Task::create(
            TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675cee'),
            TaskTitle::fromString('Second task'),
        );

        self::assertEquals([new TaskCreated($first->state()->id, $first->state()->title)], $first->pullDomainEvents());
        self::assertEquals([new TaskCreated($second->state()->id, $second->state()->title)], $second->pullDomainEvents());
    }

    #[Test]
    public function stateReturnsSnapshotThatIsUnaffectedByLaterActions(): void
    {
        $task = $this->task();
        $original = $task->state();
        $title = TaskTitle::fromString('Updated task');
        $completedAt = new DateTimeImmutable('2026-09-23T12:00:00Z');

        $task->rename($title);
        $task->complete($completedAt);
        $completed = $task->state();
        $task->reopen();

        self::assertSame('Complete the task', $original->title->value);
        self::assertSame(TaskStatus::Pending, $original->status);
        self::assertNull($original->completedAt);
        self::assertSame($original->id, $completed->id);
        self::assertSame($title, $completed->title);
        self::assertSame(TaskStatus::Completed, $completed->status);
        self::assertSame($completedAt, $completed->completedAt);
        self::assertSame(TaskStatus::Pending, $task->state()->status);
        self::assertNull($task->state()->completedAt);
    }

    #[Test]
    public function stateRoundTripPreservesDomainValuesWithoutTransferringEvents(): void
    {
        $task = $this->task();
        $completedAt = new DateTimeImmutable('2026-09-23T12:00:00Z');
        $task->complete($completedAt);
        $state = $task->state();

        $restored = Task::reconstitute($state);

        self::assertEquals($state, $restored->state());
        self::assertSame([], $restored->pullDomainEvents());
        self::assertEquals([
            new TaskCreated($state->id, $state->title),
            new TaskCompleted($state->id, $completedAt),
        ], $task->pullDomainEvents());

        $restored->rename(TaskTitle::fromString('Restored task title'));

        self::assertSame($state->title, $task->state()->title);
        self::assertSame(TaskStatus::Completed, $state->status);
    }

    private function task(): Task
    {
        return Task::create(
            TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced'),
            TaskTitle::fromString('Complete the task'),
        );
    }
}
