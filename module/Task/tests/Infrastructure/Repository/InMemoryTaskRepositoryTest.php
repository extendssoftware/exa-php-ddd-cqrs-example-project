<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Infrastructure\Repository;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCreated;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskState;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Repository\InMemoryTaskRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InMemoryTaskRepositoryTest extends TestCase
{
    #[Test]
    public function findReturnsNullForUnknownId(): void
    {
        $repository = new InMemoryTaskRepository();

        self::assertNull($repository->find($this->id()));
    }

    #[Test]
    public function saveAndFindRestoreStateWithoutTransferringOrConsumingEvents(): void
    {
        $repository = new InMemoryTaskRepository();
        $task = Task::create($this->id(), TaskTitle::fromString('Example task'));
        $state = $task->state();

        $repository->save($task);
        $loaded = $repository->find(TaskId::fromString($state->id->value));

        self::assertNotNull($loaded);
        self::assertNotSame($task, $loaded);
        self::assertEquals($state, $loaded->state());
        self::assertSame([], $loaded->pullDomainEvents());
        self::assertEquals([new TaskCreated($state->id, $state->title)], $task->pullDomainEvents());
    }

    #[Test]
    public function changesAreStoredOnlyWhenExplicitlySaved(): void
    {
        $repository = new InMemoryTaskRepository();
        $task = Task::create($this->id(), TaskTitle::fromString('Original title'));
        $original = $task->state();
        $repository->save($task);

        $task->rename(TaskTitle::fromString('Unsaved title'));

        $loaded = $repository->find($original->id);
        self::assertNotNull($loaded);
        self::assertEquals($original, $loaded->state());

        $loaded->complete(new DateTimeImmutable('2026-09-23T12:00:00Z'));
        $another = $repository->find($original->id);
        self::assertNotNull($another);
        self::assertNotSame($loaded, $another);
        self::assertEquals($original, $another->state());

        $repository->save($loaded);

        self::assertEquals($loaded->state(), $repository->find($original->id)?->state());
    }

    #[Test]
    public function savePreservesHistoricalTitleAndCompletionTime(): void
    {
        $repository = new InMemoryTaskRepository();
        $state = new TaskState(
            $this->id(),
            TaskTitle::reconstitute(str_repeat('a', 101)),
            TaskStatus::Completed,
            new DateTimeImmutable('2026-09-23T14:30:00.123456+02:00'),
        );

        $repository->save(Task::reconstitute($state));

        self::assertEquals($state, $repository->find($state->id)?->state());
    }

    #[Test]
    public function tasksAndRepositoryInstancesHaveIndependentStorage(): void
    {
        $repository = new InMemoryTaskRepository();
        $first = Task::create($this->id(), TaskTitle::fromString('First task'));
        $second = Task::create(
            TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675cee'),
            TaskTitle::fromString('Second task'),
        );
        $repository->save($first);
        $repository->save($second);
        $first->rename(TaskTitle::fromString('Updated first task'));
        $repository->save($first);

        self::assertEquals($first->state(), $repository->find($first->state()->id)?->state());
        self::assertEquals($second->state(), $repository->find($second->state()->id)?->state());
        self::assertNull(new InMemoryTaskRepository()->find($first->state()->id));
    }

    private function id(): TaskId
    {
        return TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
    }
}
