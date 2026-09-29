<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Outbox;

use DateTimeImmutable;
use DateTimeZone;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\EventSerializerInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\SerializedEvent;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCreated;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskDeleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskRenamed;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskReopened;
use InvalidArgumentException;
use JsonException;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class TaskEventSerializer implements EventSerializerInterface
{
    /**
     * @throws InvalidArgumentException When the event is not a supported Task event.
     * @throws JsonException When an event contains data that cannot be encoded as JSON.
     */
    public function serialize(DomainEventInterface $event): SerializedEvent
    {
        [$type, $payload] = match (true) {
            $event instanceof TaskCreated => [
                'task.created',
                [
                    'taskId' => $event->taskId->value,
                    'title' => $event->title->value,
                    'createdAt' => $this->timestamp($event->createdAt),
                ],
            ],
            $event instanceof TaskRenamed => [
                'task.renamed',
                [
                    'taskId' => $event->taskId->value,
                    'title' => $event->title->value,
                ],
            ],
            $event instanceof TaskCompleted => [
                'task.completed',
                [
                    'taskId' => $event->taskId->value,
                    'completedAt' => $this->timestamp($event->completedAt),
                ],
            ],
            $event instanceof TaskReopened => ['task.reopened', ['taskId' => $event->taskId->value]],
            $event instanceof TaskDeleted => ['task.deleted', ['taskId' => $event->taskId->value]],
            default => throw new InvalidArgumentException('Unsupported event type: ' . $event::class),
        };

        return new SerializedEvent($type, 1, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function timestamp(DateTimeImmutable $time): string
    {
        return $time
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.u\Z');
    }
}
