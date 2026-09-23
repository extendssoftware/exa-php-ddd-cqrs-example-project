<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Clock;

use DateTimeImmutable;
use DateTimeZone;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Clock\ClockInterface;

final readonly class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
