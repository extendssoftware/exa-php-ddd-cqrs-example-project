<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Infrastructure\Outbox;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCreated;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskDeleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskRenamed;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskReopened;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Outbox\TaskEventSerializer;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class TaskEventSerializerTest extends TestCase
{
    /**
     * @return array<string, array{DomainEventInterface, string, array<string, string>}>
     */
    public static function events(): array
    {
        $id = TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');
        $title = TaskTitle::fromString('Task "日本語"');
        $time = new DateTimeImmutable('2020-01-02T14:30:00.123456+02:00');
        $identity = ['taskId' => $id->value];

        return [
            'created' => [
                new TaskCreated($id, $title, $time),
                'task.created',
                $identity + [
                    'title' => $title->value,
                    'createdAt' => '2020-01-02T12:30:00.123456Z',
                ],
            ],
            'renamed' => [new TaskRenamed($id, $title), 'task.renamed', $identity + ['title' => $title->value]],
            'completed' => [
                new TaskCompleted($id, $time),
                'task.completed',
                $identity + [
                    'completedAt' => '2020-01-02T12:30:00.123456Z',
                ],
            ],
            'reopened' => [new TaskReopened($id), 'task.reopened', $identity],
            'deleted' => [new TaskDeleted($id), 'task.deleted', $identity],
        ];
    }

    /**
     * @param array<string, string> $payload
     */
    #[Test]
    #[DataProvider('events')]
    public function serializesVersionedEventPayload(DomainEventInterface $event, string $type, array $payload): void
    {
        $message = new TaskEventSerializer()->serialize($event);

        self::assertSame($type, $message->type);
        self::assertSame(1, $message->version);
        self::assertSame($payload, json_decode($message->payload, true, flags: JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function rejectsUnsupportedEvents(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TaskEventSerializer()->serialize(new readonly class implements DomainEventInterface {});
    }

    #[Test]
    public function rejectsInvalidJsonPayload(): void
    {
        $this->expectException(JsonException::class);

        new TaskEventSerializer()->serialize(
            new TaskRenamed(
                TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced'),
                TaskTitle::reconstitute("Invalid\xFF"),
            ),
        );
    }
}
