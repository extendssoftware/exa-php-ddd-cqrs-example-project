<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Tests\Infrastructure\Clock;

use DateTimeImmutable;
use DateTimeZone;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Clock\ClockInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Clock\SystemClock;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function date_default_timezone_get;
use function date_default_timezone_set;

final class SystemClockTest extends TestCase
{
    #[Test]
    public function nowReturnsCurrentTimeInUtcRegardlessOfDefaultTimezone(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Amsterdam');

        try {
            $clock = new SystemClock();
            $before = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $now = $clock->now();
            $after = new DateTimeImmutable('now', new DateTimeZone('UTC'));

            /** @noinspection PhpConditionAlreadyCheckedInspection */
            self::assertInstanceOf(ClockInterface::class, $clock);
            self::assertGreaterThanOrEqual($before, $now);
            self::assertLessThanOrEqual($after, $now);
            self::assertSame('UTC', $now->getTimezone()->getName());
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }
}
