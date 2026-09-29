<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Command\CompleteTask;

use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CompleteTask\CompleteTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CompleteTask\CompleteTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Tests\Support\TaskCommandIntegrationTestCase;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

use function json_decode;

#[Group('integration')]
final class CompleteTaskHandlerIntegrationTest extends TaskCommandIntegrationTestCase
{
    private CompleteTaskHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->services->getService(CompleteTaskHandler::class);
    }

    #[Test]
    public function invokeCommitsTaskChangeAndSerializedEventTogether(): void
    {
        $original = $this->seed(TaskStatus::Open);

        ($this->handler)(new CompleteTask(self::ID));

        self::assertFalse($this->pdo->inTransaction());
        $stored = $this->repository->find($original->id);
        self::assertNotNull($stored);
        self::assertSame(TaskStatus::Completed, $stored->state()->status);
        self::assertSame('2026-09-29 12:00:00.123456', $stored->state()->completedAt?->format('Y-m-d H:i:s.u'));
        self::assertEquals($original->createdAt, $stored->state()->createdAt);
        $rows = $this->pdo->query('SELECT event_type, event_version, payload FROM outbox')->fetchAll();
        self::assertCount(1, $rows);
        self::assertSame('task.completed', $rows[0]['event_type']);
        self::assertSame(1, $rows[0]['event_version']);
        self::assertEquals(['taskId' => self::ID, 'completedAt' => '2026-09-29T12:00:00.123456Z'], json_decode($rows[0]['payload'], true));
    }

    #[Test]
    public function invokeRollsBackTaskChangeWhenOutboxWriteFails(): void
    {
        $original = $this->seed(TaskStatus::Open);
        $this->pdo->exec('ALTER TABLE outbox MODIFY event_type VARCHAR(1) NOT NULL');
        $this->expectException(PDOException::class);

        try {
            ($this->handler)(new CompleteTask(self::ID));
        } finally {
            self::assertFalse($this->pdo->inTransaction());
            self::assertEquals($original, $this->repository->find($original->id)?->state());
            self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
        }
    }
}
