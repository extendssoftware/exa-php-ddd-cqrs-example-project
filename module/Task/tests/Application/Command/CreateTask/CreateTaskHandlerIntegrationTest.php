<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Command\CreateTask;

use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask\GetTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask\GetTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Tests\Support\TaskCommandIntegrationTestCase;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

use function json_decode;

#[Group('integration')]
final class CreateTaskHandlerIntegrationTest extends TaskCommandIntegrationTestCase
{
    private CreateTaskHandler $handler;
    private GetTaskHandler $queryHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->services->getService(CreateTaskHandler::class);
        $this->queryHandler = $this->services->getService(GetTaskHandler::class);
    }

    #[Test]
    public function invokeCommitsTaskAndSerializedEventTogether(): void
    {
        ($this->handler)(new CreateTask(self::ID, 'Example task'));

        self::assertFalse($this->pdo->inTransaction());
        $task = $this->repository->find(TaskId::fromString(self::ID));
        self::assertNotNull($task);
        $rows = $this->pdo->query('SELECT event_type, event_version, payload FROM outbox')->fetchAll();
        self::assertCount(1, $rows);
        self::assertSame('task.created', $rows[0]['event_type']);
        self::assertSame(1, $rows[0]['event_version']);
        self::assertEquals([
            'taskId' => self::ID,
            'title' => 'Example task',
            'createdAt' => '2026-09-29T12:00:00.123456Z',
        ], json_decode($rows[0]['payload'], true));
        self::assertSame('2026-09-29 12:00:00.123456', $task->state()->createdAt->format('Y-m-d H:i:s.u'));
        $result = ($this->queryHandler)(new GetTask(self::ID));
        self::assertSame(self::ID, $result->taskId);
        self::assertSame('Example task', $result->title);
        self::assertEquals($task->state()->createdAt, $result->createdAt);
        self::assertSame(1, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
    }

    #[Test]
    public function invokeRollsBackTaskWhenOutboxWriteFails(): void
    {
        $this->pdo->exec('ALTER TABLE outbox MODIFY event_type VARCHAR(1) NOT NULL');
        $this->expectException(PDOException::class);

        try {
            ($this->handler)(new CreateTask(self::ID, 'Example task'));
        } finally {
            self::assertFalse($this->pdo->inTransaction());
            self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM task')->fetchColumn());
            self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
        }
    }

    #[Test]
    public function invokeDoesNotAppendEventWhenTaskAlreadyExists(): void
    {
        ($this->handler)(new CreateTask(self::ID, 'Original task'));
        $this->expectException(TaskAlreadyExists::class);

        try {
            ($this->handler)(new CreateTask(self::ID, 'Duplicate task'));
        } finally {
            self::assertFalse($this->pdo->inTransaction());
            self::assertSame(1, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
            self::assertSame('Original task', $this->pdo->query('SELECT title FROM task')->fetchColumn());
        }
    }
}
