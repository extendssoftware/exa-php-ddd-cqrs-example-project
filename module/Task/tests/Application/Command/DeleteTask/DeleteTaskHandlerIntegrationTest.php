<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Command\DeleteTask;

use ExtendsSoftware\ExaPHPExample\Task\Application\Command\DeleteTask\DeleteTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\DeleteTask\DeleteTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Tests\Support\TaskCommandIntegrationTestCase;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

use function json_decode;

#[Group('integration')]
final class DeleteTaskHandlerIntegrationTest extends TaskCommandIntegrationTestCase
{
    private DeleteTaskHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->services->getService(DeleteTaskHandler::class);
    }

    #[Test]
    public function invokeCommitsTaskChangeAndSerializedEventTogether(): void
    {
        $original = $this->seed(TaskStatus::Pending);

        ($this->handler)(new DeleteTask(self::ID));

        self::assertFalse($this->pdo->inTransaction());
        $stored = $this->repository->find($original->id);
        self::assertNull($stored);
        $rows = $this->pdo->query('SELECT event_type, event_version, payload FROM outbox')->fetchAll();
        self::assertCount(1, $rows);
        self::assertSame('task.deleted', $rows[0]['event_type']);
        self::assertSame(1, $rows[0]['event_version']);
        self::assertEquals(['taskId' => self::ID], json_decode($rows[0]['payload'], true));
    }

    #[Test]
    public function invokeRollsBackTaskChangeWhenOutboxWriteFails(): void
    {
        $original = $this->seed(TaskStatus::Pending);
        $this->pdo->exec('ALTER TABLE outbox MODIFY event_type VARCHAR(1) NOT NULL');
        $this->expectException(PDOException::class);

        try {
            ($this->handler)(new DeleteTask(self::ID));
        } finally {
            self::assertFalse($this->pdo->inTransaction());
            self::assertEquals($original, $this->repository->find($original->id)?->state());
            self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
        }
    }
}
