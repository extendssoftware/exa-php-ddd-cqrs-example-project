<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Command;

use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CompleteTask\CompleteTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CompleteTask\CompleteTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\DeleteTask\DeleteTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\DeleteTask\DeleteTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\RenameTask\RenameTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\RenameTask\RenameTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\ReopenTask\ReopenTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\ReopenTask\ReopenTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Tests\Support\TaskCommandIntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

use function array_column;
use function json_decode;

#[Group('integration')]
final class TaskLifecycleIntegrationTest extends TaskCommandIntegrationTestCase
{
    #[Test]
    public function handlersPersistLifecycleChangesAndTheirEvents(): void
    {
        $original = $this->seed(TaskStatus::Open);
        $id = $original->id;

        $this->execute('rename');
        self::assertSame('Renamed task', $this->repository->find($id)?->state()->title->value);
        $this->execute('complete');
        $completed = $this->repository->find($id)?->state();
        self::assertSame(TaskStatus::Completed, $completed?->status);
        self::assertSame('2026-09-29 12:00:00.123456', $completed?->completedAt?->format('Y-m-d H:i:s.u'));
        self::assertEquals($original->createdAt, $completed?->createdAt);
        $this->execute('reopen');
        $reopened = $this->repository->find($id)?->state();
        self::assertSame(TaskStatus::Open, $reopened?->status);
        self::assertNull($reopened?->completedAt);
        $this->execute('delete');
        self::assertNull($this->repository->find($id));
        self::assertFalse($this->pdo->inTransaction());

        $rows = $this->pdo->query('SELECT event_type, event_version, payload FROM outbox ORDER BY id')->fetchAll();
        self::assertSame(['task.renamed', 'task.completed', 'task.reopened', 'task.deleted'], array_column($rows, 'event_type'));
        foreach ($rows as $row) {
            self::assertSame(1, $row['event_version']);
            self::assertSame(self::ID, json_decode($row['payload'], true)['taskId']);
        }
        self::assertSame('Renamed task', json_decode($rows[0]['payload'], true)['title']);
        self::assertSame('2026-09-29T12:00:00.123456Z', json_decode($rows[1]['payload'], true)['completedAt']);
    }

    private function execute(string $operation): void
    {
        [$handler, $command] = match ($operation) {
            'rename' => [RenameTaskHandler::class, new RenameTask(self::ID, 'Renamed task')],
            'complete' => [CompleteTaskHandler::class, new CompleteTask(self::ID)],
            'reopen' => [ReopenTaskHandler::class, new ReopenTask(self::ID)],
            'delete' => [DeleteTaskHandler::class, new DeleteTask(self::ID)],
        };
        $this->services->getService($handler)($command);
    }
}
