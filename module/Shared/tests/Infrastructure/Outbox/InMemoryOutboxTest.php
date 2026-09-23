<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Tests\Infrastructure\Outbox;

use ExtendsSoftware\ExaPHPExample\Shared\Domain\DomainEventInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\InMemoryOutbox;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InMemoryOutboxTest extends TestCase
{
    #[Test]
    public function appendRetainsEventsInOrderAndAllDoesNotDrainThem(): void
    {
        $outbox = new InMemoryOutbox();
        $first = new readonly class implements DomainEventInterface {};
        $second = new readonly class implements DomainEventInterface {};

        self::assertSame([], $outbox->all());

        $outbox->append($first);
        $outbox->append($second);

        self::assertSame([$first, $second], $outbox->all());
        self::assertSame([$first, $second], $outbox->all());
    }

    #[Test]
    public function returnedCollectionAndOtherInstancesAreIndependent(): void
    {
        $outbox = new InMemoryOutbox();
        $event = new readonly class implements DomainEventInterface {};
        $outbox->append($event);
        $events = $outbox->all();
        unset($events[0]);

        self::assertSame([$event], $outbox->all());
        self::assertSame([], new InMemoryOutbox()->all());
    }
}
