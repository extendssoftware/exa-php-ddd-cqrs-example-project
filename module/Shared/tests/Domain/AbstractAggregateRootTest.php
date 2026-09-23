<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Tests\Domain;

use ExtendsSoftware\ExaPHPExample\Shared\Domain\AbstractAggregateRoot;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\RecordsDomainEventsInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AbstractAggregateRootTest extends TestCase
{
    #[Test]
    public function pullReturnsEventsInOrderAndClearsCollection(): void
    {
        $aggregate = new class extends AbstractAggregateRoot {
            public function record(DomainEventInterface $event): void
            {
                $this->recordThat($event);
            }
        };
        $first = new readonly class implements DomainEventInterface {};
        $second = new readonly class implements DomainEventInterface {};

        self::assertInstanceOf(RecordsDomainEventsInterface::class, $aggregate);
        self::assertSame([], $aggregate->pullDomainEvents());

        $aggregate->record($first);
        $aggregate->record($second);

        self::assertSame([$first, $second], $aggregate->pullDomainEvents());
        self::assertSame([], $aggregate->pullDomainEvents());

        $aggregate->record($second);

        self::assertSame([$second], $aggregate->pullDomainEvents());
    }

    #[Test]
    public function aggregatesKeepSeparateCollections(): void
    {
        $first = new class extends AbstractAggregateRoot {
            public function record(DomainEventInterface $event): void
            {
                $this->recordThat($event);
            }
        };
        $second = new class extends AbstractAggregateRoot {};
        $event = new readonly class implements DomainEventInterface {};

        $first->record($event);

        self::assertSame([], $second->pullDomainEvents());
        self::assertSame([$event], $first->pullDomainEvents());
    }
}
