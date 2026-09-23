<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Tests\Infrastructure\Clock;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Clock\ClockInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Clock\FrozenClock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FrozenClockTest extends TestCase
{
    #[Test]
    public function nowAlwaysReturnsSuppliedTimeIncludingTimezoneAndMicroseconds(): void
    {
        $time = new DateTimeImmutable('2026-09-23T14:30:00.123456+02:00');
        $clock = new FrozenClock($time);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertInstanceOf(ClockInterface::class, $clock);
        self::assertSame($time, $clock->now());
        self::assertSame($time, $clock->now());
    }

    #[Test]
    public function modifyingReturnedTimeDoesNotChangeClock(): void
    {
        $time = new DateTimeImmutable('2026-09-23T12:00:00Z');
        $clock = new FrozenClock($time);

        $later = $clock
            ->now()
            ->modify('+1 day');

        self::assertSame('2026-09-24', $later->format('Y-m-d'));
        self::assertSame($time, $clock->now());
    }
}
