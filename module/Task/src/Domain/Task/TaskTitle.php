<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task;

use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskTitle;

final readonly class TaskTitle
{
    private function __construct(private(set) string $value) {}

    /**
     * @throws InvalidTaskTitle When the value is not valid UTF-8 or is outside 3–100 Unicode code points.
     */
    public static function fromString(string $value): self
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            throw new InvalidTaskTitle();
        }

        $length = mb_strlen($value, 'UTF-8');
        if ($length < 3 || $length > 100) {
            throw new InvalidTaskTitle();
        }

        return new self($value);
    }

    public static function reconstitute(string $value): self
    {
        return new self($value);
    }
}
