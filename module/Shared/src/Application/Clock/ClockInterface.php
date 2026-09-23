<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Application\Clock;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
