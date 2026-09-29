<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Tests\Infrastructure\Outbox;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Clock\FrozenClock;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\EventSerializerInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\PdoOutbox;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\SerializedEvent;
use JsonException;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

use function file_get_contents;
use function json_decode;
use function str_replace;

#[Group('integration')]
final class PdoOutboxIntegrationTest extends TestCase
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
    public function appendsDistinctMessagesWithJsonAndUtcRecordingTime(): void
    {
        $event = new readonly class implements DomainEventInterface {};
        $serializer = $this->createMock(EventSerializerInterface::class);
        $serializer->expects(self::exactly(2))->method('serialize')->with(self::identicalTo($event))
            ->willReturn(new SerializedEvent('example.created', 2, '{"title":"Example"}'));
        $outbox = new PdoOutbox($this->pdo, $serializer, $this->clock());

        $this->pdo->beginTransaction();
        $outbox->append($event);
        $outbox->append($event);
        self::assertTrue($this->pdo->inTransaction());
        $this->pdo->commit();

        $rows = $this->pdo->query('SELECT BIN_TO_UUID(id) AS id, event_type, event_version, payload, recorded_at FROM outbox ORDER BY id')->fetchAll();
        self::assertCount(2, $rows);
        self::assertNotSame($rows[0]['id'], $rows[1]['id']);
        foreach ($rows as $row) {
            self::assertSame(7, Uuid::fromString($row['id'])->getVersion());
            self::assertSame('example.created', $row['event_type']);
            self::assertSame(2, $row['event_version']);
            self::assertSame(['title' => 'Example'], json_decode($row['payload'], true));
            self::assertSame('2026-09-29 12:00:00.123456', $row['recorded_at']);
        }
    }

    #[Test]
    public function callerRollbackRemovesAppendedMessages(): void
    {
        $serializer = $this->createStub(EventSerializerInterface::class);
        $serializer->method('serialize')->willReturn(new SerializedEvent('example.created', 1, '{}'));
        $outbox = new PdoOutbox($this->pdo, $serializer, $this->clock());

        $this->pdo->beginTransaction();
        $outbox->append(new readonly class implements DomainEventInterface {});
        $this->pdo->rollBack();

        self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
    }

    #[Test]
    public function serializationFailureLeavesNoMessage(): void
    {
        $failure = new JsonException('Cannot serialize');
        $serializer = $this->createMock(EventSerializerInterface::class);
        $serializer->expects(self::once())->method('serialize')->willThrowException($failure);
        $outbox = new PdoOutbox($this->pdo, $serializer, $this->clock());

        $this->expectExceptionObject($failure);
        try {
            $outbox->append(new readonly class implements DomainEventInterface {});
        } finally {
            self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
        }
    }

    #[Test]
    public function databaseFailurePropagates(): void
    {
        $serializer = $this->createStub(EventSerializerInterface::class);
        $serializer->method('serialize')->willReturn(new SerializedEvent('example.created', 1, 'invalid JSON'));
        $outbox = new PdoOutbox($this->pdo, $serializer, $this->clock());

        $this->expectException(PDOException::class);
        try {
            $outbox->append(new readonly class implements DomainEventInterface {});
        } finally {
            self::assertSame(0, $this->pdo->query('SELECT COUNT(*) FROM outbox')->fetchColumn());
        }
    }

    private function clock(): FrozenClock
    {
        return new FrozenClock(new DateTimeImmutable('2026-09-29T14:00:00.123456+02:00'));
    }
}
