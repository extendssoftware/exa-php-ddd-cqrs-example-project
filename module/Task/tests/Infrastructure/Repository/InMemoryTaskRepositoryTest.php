<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Infrastructure\Repository;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCreated;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskDeleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskRenamed;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskState;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Repository\InMemoryTaskRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function count;
use function str_repeat;

final class InMemoryTaskRepositoryTest extends TestCase
{
    #[Test]
    public function findReturnsNullForUnknownId(): void
    {
        $outbox = $this->outboxExpecting();
        $repository = new InMemoryTaskRepository($outbox);

        self::assertNull($repository->find($this->id()));
    }

    #[Test]
    public function addStoresEventsInOutboxAndFindRestoresOnlyState(): void
    {
        $outbox = $this->outboxExpecting(
            new TaskCreated($this->id(), TaskTitle::fromString('Example task')),
        );
        $repository = new InMemoryTaskRepository($outbox);
        $task = Task::create($this->id(), TaskTitle::fromString('Example task'));
        $state = $task->state();

        $repository->add($task);
        $loaded = $repository->find(TaskId::fromString($state->id->value));

        self::assertNotNull($loaded);
        self::assertNotSame($task, $loaded);
        self::assertEquals($state, $loaded->state());
        self::assertSame([], $loaded->pullDomainEvents());
        self::assertSame([], $task->pullDomainEvents());
    }

    #[Test]
    public function changesAreStoredOnlyWhenExplicitlyUpdated(): void
    {
        $outbox = $this->outboxExpecting(
            new TaskCreated($this->id(), TaskTitle::fromString('Original title')),
            new TaskCompleted($this->id(), new DateTimeImmutable('2026-09-23T12:00:00Z')),
        );
        $repository = new InMemoryTaskRepository($outbox);
        $task = Task::create($this->id(), TaskTitle::fromString('Original title'));
        $original = $task->state();
        $repository->add($task);

        $task->rename(TaskTitle::fromString('Unsaved title'));

        $loaded = $repository->find($original->id);
        self::assertNotNull($loaded);
        self::assertEquals($original, $loaded->state());

        $completedAt = new DateTimeImmutable('2026-09-23T12:00:00Z');
        $loaded->complete($completedAt);
        $another = $repository->find($original->id);
        self::assertNotNull($another);
        self::assertNotSame($loaded, $another);
        self::assertEquals($original, $another->state());

        $repository->update($loaded);

        self::assertEquals(
            $loaded->state(),
            $repository
                ->find($original->id)
                ?->state(),
        );
        self::assertSame([], $loaded->pullDomainEvents());
        self::assertSame([],
            $repository
                ->find($original->id)
                ?->pullDomainEvents());
    }

    #[Test]
    public function addPreservesHistoricalTitleAndCompletionTime(): void
    {
        $outbox = $this->outboxExpecting();
        $repository = new InMemoryTaskRepository($outbox);
        $state = new TaskState(
            $this->id(),
            TaskTitle::reconstitute(str_repeat('a', 101)),
            TaskStatus::Completed,
            new DateTimeImmutable('2026-09-23T14:30:00.123456+02:00'),
        );

        $repository->add(Task::reconstitute($state));

        self::assertEquals(
            $state,
            $repository
                ->find($state->id)
                ?->state(),
        );
    }

    #[Test]
    public function tasksAndRepositoryInstancesHaveIndependentStorage(): void
    {
        $outbox = $this->outboxExpecting(
            new TaskCreated($this->id(), TaskTitle::fromString('First task')),
            new TaskCreated(
                TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675cee'),
                TaskTitle::fromString('Second task'),
            ),
            new TaskRenamed($this->id(), TaskTitle::fromString('Updated first task')),
        );
        $repository = new InMemoryTaskRepository($outbox);
        $first = Task::create($this->id(), TaskTitle::fromString('First task'));
        $second = Task::create(
            TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675cee'),
            TaskTitle::fromString('Second task'),
        );
        $repository->add($first);
        $repository->add($second);
        $first->rename(TaskTitle::fromString('Updated first task'));
        $repository->update($first);

        self::assertEquals(
            $first->state(),
            $repository
                ->find($first->state()->id)
                ?->state(),
        );
        self::assertEquals(
            $second->state(),
            $repository
                ->find($second->state()->id)
                ?->state(),
        );
        self::assertNull(new InMemoryTaskRepository($this->outboxExpecting())->find($first->state()->id));
    }

    #[Test]
    public function addRejectsDuplicateIdWithoutOverwritingStoredState(): void
    {
        $outbox = $this->outboxExpecting(
            new TaskCreated($this->id(), TaskTitle::fromString('Original title')),
        );
        $repository = new InMemoryTaskRepository($outbox);
        $original = Task::create($this->id(), TaskTitle::fromString('Original title'));
        $repository->add($original);
        $duplicate = Task::create($this->id(), TaskTitle::fromString('Different title'));
        $state = $duplicate->state();

        $this->expectException(TaskAlreadyExists::class);
        $this->expectExceptionMessageIsOrContains('Task "01902424-9b00-7cc3-98c4-2c1f7c675ced" already exists.');

        try {
            $repository->add($duplicate);
        } catch (TaskAlreadyExists $exception) {
            self::assertSame($state->id, $exception->taskId);

            throw $exception;
        } finally {
            self::assertEquals(
                $original->state(),
                $repository
                    ->find($this->id())
                    ?->state(),
            );
            self::assertEquals([new TaskCreated($state->id, $state->title)], $duplicate->pullDomainEvents());
        }
    }

    #[Test]
    public function updateRejectsMissingTaskWithoutInsertingIt(): void
    {
        $outbox = $this->outboxExpecting();
        $repository = new InMemoryTaskRepository($outbox);
        $task = Task::create($this->id(), TaskTitle::fromString('Missing task'));
        $state = $task->state();

        $this->expectException(TaskNotFound::class);
        $this->expectExceptionMessageIsOrContains('Task "01902424-9b00-7cc3-98c4-2c1f7c675ced" was not found.');

        try {
            $repository->update($task);
        } catch (TaskNotFound $exception) {
            self::assertSame($state->id, $exception->taskId);

            throw $exception;
        } finally {
            self::assertNull($repository->find($state->id));
            self::assertEquals([new TaskCreated($state->id, $state->title)], $task->pullDomainEvents());
        }
    }

    #[Test]
    public function updateAcceptsUnchangedState(): void
    {
        $outbox = $this->outboxExpecting(
            new TaskCreated($this->id(), TaskTitle::fromString('Existing task')),
        );
        $repository = new InMemoryTaskRepository($outbox);
        $task = Task::create($this->id(), TaskTitle::fromString('Existing task'));
        $repository->add($task);

        $repository->update($task);

        self::assertEquals(
            $task->state(),
            $repository
                ->find($this->id())
                ?->state(),
        );
    }

    #[Test]
    public function removePermanentlyRemovesOnlyRequestedTaskAndStoresEvents(): void
    {
        $outbox = $this->outboxExpecting(
            new TaskCreated($this->id(), TaskTitle::fromString('Task to delete')),
            new TaskCreated(
                TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675cee'),
                TaskTitle::fromString('Other task'),
            ),
            new TaskDeleted($this->id()),
        );
        $repository = new InMemoryTaskRepository($outbox);
        $task = Task::create($this->id(), TaskTitle::fromString('Task to delete'));
        $other = Task::create(
            TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675cee'),
            TaskTitle::fromString('Other task'),
        );
        $repository->add($task);
        $repository->add($other);
        $task->pullDomainEvents();

        $task->delete();
        $repository->remove($task);

        self::assertNull($repository->find($this->id()));
        self::assertEquals(
            $other->state(),
            $repository
                ->find($other->state()->id)
                ?->state(),
        );
        self::assertSame([], $task->pullDomainEvents());
    }

    #[Test]
    public function removeRejectsMissingTask(): void
    {
        $outbox = $this->outboxExpecting();
        $repository = new InMemoryTaskRepository($outbox);
        $id = $this->id();
        $task = Task::create($id, TaskTitle::fromString('Missing task'));

        $this->expectException(TaskNotFound::class);
        $this->expectExceptionMessageIsOrContains('Task "01902424-9b00-7cc3-98c4-2c1f7c675ced" was not found.');

        try {
            $repository->remove($task);
        } catch (TaskNotFound $exception) {
            self::assertSame($id, $exception->taskId);
            self::assertEquals([new TaskCreated($id, $task->state()->title)], $task->pullDomainEvents());

            throw $exception;
        }
    }

    #[Test]
    public function updateAfterRemovalRejectsChangesWithoutRecreatingTask(): void
    {
        $outbox = $this->outboxExpecting(
            new TaskCreated($this->id(), TaskTitle::fromString('Task to delete')),
            new TaskDeleted($this->id()),
        );
        $repository = new InMemoryTaskRepository($outbox);
        $task = Task::create($this->id(), TaskTitle::fromString('Task to delete'));
        $repository->add($task);
        $task->pullDomainEvents();
        $task->delete();
        $repository->remove($task);
        $title = TaskTitle::fromString('Changed after deletion');

        $task->rename($title);

        $this->expectException(TaskNotFound::class);

        try {
            $repository->update($task);
        } catch (TaskNotFound $exception) {
            self::assertSame($task->state()->id, $exception->taskId);

            throw $exception;
        } finally {
            self::assertNull($repository->find($this->id()));
            self::assertEquals([
                new TaskRenamed($this->id(), $title),
            ], $task->pullDomainEvents());
        }
    }

    #[Test]
    public function updateOfStaleCopyFailsAfterAnotherInstanceDeletesTask(): void
    {
        $outbox = $this->outboxExpecting(
            new TaskCreated($this->id(), TaskTitle::fromString('Task to delete')),
            new TaskDeleted($this->id()),
        );
        $repository = new InMemoryTaskRepository($outbox);
        $task = Task::create($this->id(), TaskTitle::fromString('Task to delete'));
        $repository->add($task);
        $stale = $repository->find($this->id());
        self::assertNotNull($stale);
        $task->delete();
        $repository->remove($task);

        $stale->complete(new DateTimeImmutable('2026-09-23T12:00:00Z'));

        $this->expectException(TaskNotFound::class);

        try {
            $repository->update($stale);
        } finally {
            self::assertNull($repository->find($this->id()));
        }
    }

    #[Test]
    public function writesAppendAllPendingEventsInOrderWithoutDuplicates(): void
    {
        $outbox = $this->outboxExpecting(
            new TaskCreated($this->id(), TaskTitle::fromString('Original title')),
            new TaskRenamed($this->id(), TaskTitle::fromString('Renamed title')),
            new TaskDeleted($this->id()),
        );
        $repository = new InMemoryTaskRepository($outbox);
        $task = Task::create($this->id(), TaskTitle::fromString('Original title'));
        $original = $task->state();
        $title = TaskTitle::fromString('Renamed title');
        $task->rename($title);

        $repository->add($task);
        $repository->update($task);
        $task->delete();
        $repository->remove($task);

        self::assertSame([], $task->pullDomainEvents());
        self::assertNull($repository->find($original->id));
    }

    private function outboxExpecting(DomainEventInterface ...$events): OutboxInterface
    {
        $outbox = $this->createMock(OutboxInterface::class);
        $index = 0;
        $outbox
            ->expects(self::exactly(count($events)))
            ->method('append')
            ->willReturnCallback(static function (DomainEventInterface $event) use ($events, &$index): void {
                self::assertEquals($events[$index++], $event);
            });

        return $outbox;
    }

    private function id(): TaskId
    {
        return TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
    }
}
