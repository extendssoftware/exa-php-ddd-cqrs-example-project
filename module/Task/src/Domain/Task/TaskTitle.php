<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task;

use InvalidArgumentException;

final readonly class TaskTitle
{
    private function __construct(private(set) string $value) {}

    /**
     * @throws InvalidArgumentException When the value is not valid UTF-8 or is outside 3–100 Unicode code points.
     */
    public static function fromString(string $value): self
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            throw new InvalidArgumentException('Task title must contain between 3 and 100 characters of valid UTF-8.');
        }

        $length = mb_strlen($value, 'UTF-8');
        if ($length < 3 || $length > 100) {
            throw new InvalidArgumentException('Task title must contain between 3 and 100 characters of valid UTF-8.');
        }

        return new self($value);
    }

    public static function reconstitute(string $value): self
    {
        return new self($value);
    }
}
