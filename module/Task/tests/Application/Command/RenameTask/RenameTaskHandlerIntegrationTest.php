<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Command\RenameTask;

use ExtendsSoftware\ExaPHPExample\Task\Application\Command\RenameTask\RenameTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\RenameTask\RenameTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Tests\Support\TaskCommandIntegrationTestCase;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

use function json_decode;

#[Group('integration')]
final class RenameTaskHandlerIntegrationTest extends TaskCommandIntegrationTestCase
{
    private RenameTaskHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->services->getService(RenameTaskHandler::class);
    }

    #[Test]
    public function invokeCommitsTaskChangeAndSerializedEventTogether(): void
    {
        $original = $this->seed(TaskStatus::Open);

        ($this->handler)(new RenameTask(self::ID, 'Renamed task'));

        self::assertFalse($this->pdo->inTransaction());
        $stored = $this->repository->find($original->id);
        self::assertNotNull($stored);
        self::assertSame('Renamed task', $stored->state()->title->value);
        self::assertSame($original->status, $stored->state()->status);
        self::assertEquals($original->createdAt, $stored->state()->createdAt);
        $rows = $this->pdo->query('SELECT event_type, event_version, payload FROM outbox')->fetchAll();
        self::assertCount(1, $rows);
        self::assertSame('task.renamed', $rows[0]['event_type']);
        self::assertSame(1, $rows[0]['event_version']);
        self::assertEquals(['taskId' => self::ID, 'title' => 'Renamed task'], json_decode($rows[0]['payload'], true));
    }

    #[Test]
    public function invokeRollsBackTaskChangeWhenOutboxWriteFails(): void
    {
        $original = $this->seed(TaskStatus::Open);
        $this->pdo->exec('ALTER TABLE outbox MODIFY event_type VARCHAR(1) NOT NULL');
        $this->expectException(PDOException::class);

        try {
            ($this->handler)(new RenameTask(self::ID, 'Renamed task'));
        } finally {
            self::assertFalse($this->pdo->inTransaction());
            self::assertEquals($original, $this->repository->find($original->id)?->state());
            self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
        }
    }
}
