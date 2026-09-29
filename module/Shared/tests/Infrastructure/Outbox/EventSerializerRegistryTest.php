<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Tests\Infrastructure\Outbox;

use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\EventSerializerInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\EventSerializerRegistry;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\SerializedEvent;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EventSerializerRegistryTest extends TestCase
{
    #[Test]
    public function routesEachEventToItsRegisteredSerializer(): void
    {
        $first = new readonly class implements DomainEventInterface {};
        $second = new readonly class implements DomainEventInterface {};
        $firstMessage = new SerializedEvent('first', 1, '{}');
        $secondMessage = new SerializedEvent('second', 1, '{}');
        $firstSerializer = $this->createMock(EventSerializerInterface::class);
        $firstSerializer->expects(self::once())->method('serialize')->with(self::identicalTo($first))->willReturn($firstMessage);
        $secondSerializer = $this->createMock(EventSerializerInterface::class);
        $secondSerializer->expects(self::once())->method('serialize')->with(self::identicalTo($second))->willReturn($secondMessage);
        $registry = new EventSerializerRegistry([
            $first::class => $firstSerializer,
            $second::class => $secondSerializer,
        ]);

        self::assertSame($firstMessage, $registry->serialize($first));
        self::assertSame($secondMessage, $registry->serialize($second));
    }

    #[Test]
    public function rejectsUnregisteredEventWithoutTryingOtherSerializers(): void
    {
        $registered = new readonly class implements DomainEventInterface {};
        $unknown = new readonly class implements DomainEventInterface {};
        $serializer = $this->createMock(EventSerializerInterface::class);
        $serializer->expects(self::never())->method('serialize');
        $registry = new EventSerializerRegistry([$registered::class => $serializer]);

        $this->expectException(InvalidArgumentException::class);
        $registry->serialize($unknown);
    }

    #[Test]
    public function propagatesSelectedSerializerFailure(): void
    {
        $event = new readonly class implements DomainEventInterface {};
        $failure = new JsonException('Invalid payload');
        $serializer = $this->createMock(EventSerializerInterface::class);
        $serializer->expects(self::once())->method('serialize')->with(self::identicalTo($event))->willThrowException($failure);
        $registry = new EventSerializerRegistry([$event::class => $serializer]);

        $this->expectExceptionObject($failure);
        $registry->serialize($event);
    }
}
