<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Infrastructure\Repository;

use DateTimeImmutable;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
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
use ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Repository\PdoTaskRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function str_replace;

#[Group('integration')]
final class PdoTaskRepositoryIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $config = (require __DIR__ . '/../../../../../config/pdo.local.php.dist')[PDO::class];
        $this->pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
        $schema = file_get_contents(__DIR__ . '/../../../resources/database/schema.sql');
        $this->pdo->exec(str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $schema));
    }

    #[Test]
    public function findReturnsNullForUnknownId(): void
    {
        $repository = new PdoTaskRepository($this->pdo);

        self::assertNull($repository->find($this->id()));
    }

    #[Test]
    public function addPreservesPendingEventsAndFindRestoresOnlyState(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::create($this->id(), TaskTitle::fromString('Example task'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
        $state = $task->state();

        $repository->add($task);
        $loaded = new PdoTaskRepository($this->pdo)
            ->find(TaskId::fromString($state->id->value));

        self::assertNotNull($loaded);
        self::assertNotSame($task, $loaded);
        self::assertEquals($state, $loaded->state());
        self::assertSame([], $loaded->pullDomainEvents());
        self::assertEquals([new TaskCreated($state->id, $state->title, $state->createdAt)], $task->pullDomainEvents());
    }

    #[Test]
    public function changesAreStoredOnlyWhenExplicitlyUpdated(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::create($this->id(), TaskTitle::fromString('Original title'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
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
        self::assertEquals([new TaskCompleted($original->id, $completedAt)], $loaded->pullDomainEvents());
        self::assertSame([],
            $repository
                ->find($original->id)
                ?->pullDomainEvents());
    }

    #[Test]
    public function addPreservesHistoricalTitleAndCompletionTime(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $state = new TaskState(
            $this->id(),
            TaskTitle::reconstitute('a'),
            TaskStatus::Completed,
            new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'),
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
    public function tasksHaveIndependentStateAndRepositoryInstancesShareStorage(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $first = Task::create($this->id(), TaskTitle::fromString('First task'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
        $second = Task::create(
            TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675cee'),
            TaskTitle::fromString('Second task'),
            new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'),
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
        self::assertEquals(
            $first->state(),
            new PdoTaskRepository($this->pdo)
                ->find($first->state()->id)
                ?->state(),
        );
    }

    #[Test]
    public function addRejectsDuplicateIdWithoutOverwritingStoredState(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $original = Task::create($this->id(), TaskTitle::fromString('Original title'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
        $repository->add($original);
        $duplicate = Task::create($this->id(), TaskTitle::fromString('Different title'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
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
            self::assertEquals([new TaskCreated($state->id, $state->title, $state->createdAt)], $duplicate->pullDomainEvents());
        }
    }

    #[Test]
    public function updateRejectsMissingTaskWithoutInsertingIt(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::create($this->id(), TaskTitle::fromString('Missing task'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
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
            self::assertEquals([new TaskCreated($state->id, $state->title, $state->createdAt)], $task->pullDomainEvents());
        }
    }

    #[Test]
    public function updateAcceptsUnchangedState(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::create($this->id(), TaskTitle::fromString('Existing task'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
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
    public function removePermanentlyRemovesOnlyRequestedTaskAndPreservesEvents(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::create($this->id(), TaskTitle::fromString('Task to delete'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
        $other = Task::create(
            TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675cee'),
            TaskTitle::fromString('Other task'),
            new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'),
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
        self::assertEquals([new TaskDeleted($this->id())], $task->pullDomainEvents());
    }

    #[Test]
    public function removeRejectsMissingTask(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $id = $this->id();
        $task = Task::create($id, TaskTitle::fromString('Missing task'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));

        $this->expectException(TaskNotFound::class);
        $this->expectExceptionMessageIsOrContains('Task "01902424-9b00-7cc3-98c4-2c1f7c675ced" was not found.');

        try {
            $repository->remove($task);
        } catch (TaskNotFound $exception) {
            self::assertSame($id, $exception->taskId);
            self::assertEquals([new TaskCreated($id, $task->state()->title, $task->state()->createdAt)], $task->pullDomainEvents());

            throw $exception;
        }
    }

    #[Test]
    public function updateAfterRemovalRejectsChangesWithoutRecreatingTask(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::create($this->id(), TaskTitle::fromString('Task to delete'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
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
                new TaskDeleted($this->id()),
                new TaskRenamed($this->id(), $title),
            ], $task->pullDomainEvents());
        }
    }

    #[Test]
    public function updateOfStaleCopyFailsAfterAnotherInstanceDeletesTask(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::create($this->id(), TaskTitle::fromString('Task to delete'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
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
    public function writesLeaveAllPendingEventsAvailableToCaller(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::create($this->id(), TaskTitle::fromString('Original title'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
        $original = $task->state();
        $title = TaskTitle::fromString('Renamed title');
        $task->rename($title);

        $repository->add($task);
        $repository->update($task);
        $task->delete();
        $repository->remove($task);

        self::assertEquals([
            new TaskCreated($original->id, $original->title, $original->createdAt),
            new TaskRenamed($original->id, $title),
            new TaskDeleted($original->id),
        ], $task->pullDomainEvents());
        self::assertNull($repository->find($original->id));
    }

    #[Test]
    public function writesPreserveCreationTimeAndNormalizeCompletionTimeToUtc(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::reconstitute(new TaskState(
            $this->id(),
            TaskTitle::reconstitute('Historical task'),
            TaskStatus::InProgress,
            new DateTimeImmutable('2020-01-02T14:00:00.123456+02:00'),
            null,
        ));
        $repository->add($task);
        self::assertSame(TaskStatus::InProgress, $repository->find($this->id())?->state()->status);
        self::assertSame('2020-01-02 12:00:00.123456', $this->pdo->query('SELECT created_at FROM task')->fetchColumn());

        $task->complete(new DateTimeImmutable('2026-09-30T14:30:00.654321+02:00'));
        $task->pullDomainEvents();
        $repository = new PdoTaskRepository($this->pdo);
        $repository->update($task);
        self::assertSame('2020-01-02 12:00:00.123456', $this->pdo->query('SELECT created_at FROM task')->fetchColumn());
        self::assertSame('2026-09-30 12:30:00.654321', $this->pdo->query('SELECT completed_at FROM task')->fetchColumn());
        self::assertEquals($task->state(), $repository->find($this->id())?->state());

        $task->reopen();
        $task->pullDomainEvents();
        $repository->update($task);
        self::assertEquals($task->state(), $repository->find($this->id())?->state());
    }

    #[Test]
    public function databaseFailurePropagatesAndPreservesEvents(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::create($this->id(), TaskTitle::fromString('Example task'), new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
        $this->pdo->exec('ALTER TABLE task MODIFY title VARCHAR(5) NOT NULL');

        $this->expectException(PDOException::class);
        try {
            $repository->add($task);
        } finally {
            self::assertNull($repository->find($this->id()));
            self::assertEquals([new TaskCreated($this->id(), $task->state()->title, $task->state()->createdAt)], $task->pullDomainEvents());
        }
    }

    #[Test]
    public function writesParticipateInCallerOwnedTransactions(): void
    {
        $repository = new PdoTaskRepository($this->pdo);
        $task = Task::reconstitute(new TaskState(
            $this->id(),
            TaskTitle::reconstitute('Existing task'),
            TaskStatus::Pending,
            new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'),
            null,
        ));
        $this->pdo->beginTransaction();
        $repository->add($task);
        self::assertTrue($this->pdo->inTransaction());
        $this->pdo->rollBack();
        self::assertNull($repository->find($this->id()));

        $repository->add($task);
        $this->pdo->beginTransaction();
        $task->rename(TaskTitle::fromString('Changed task'));
        $task->pullDomainEvents();
        $repository->update($task);
        $this->pdo->rollBack();
        self::assertSame('Existing task', $repository->find($this->id())?->state()->title->value);

        $this->pdo->beginTransaction();
        $repository->remove($task);
        self::assertNull($repository->find($this->id()));
        $this->pdo->rollBack();
        self::assertNotNull($repository->find($this->id()));
    }

    private function id(): TaskId
    {
        return TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
    }
}
