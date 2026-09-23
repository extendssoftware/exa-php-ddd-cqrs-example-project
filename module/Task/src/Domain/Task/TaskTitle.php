<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task;

use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskTitle;

final readonly class TaskTitle
{
    public const int MIN_LENGTH = 3;
    public const int MAX_LENGTH = 100;

    private function __construct(private(set) string $value) {}

    /**
     * @throws InvalidTaskTitle When the value is not valid UTF-8 or its Unicode code point count is outside the limits.
     */
    public static function fromString(string $value): self
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            throw InvalidTaskTitle::invalidEncoding();
        }

        $length = mb_strlen($value, 'UTF-8');
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw InvalidTaskTitle::invalidLength(self::MIN_LENGTH, self::MAX_LENGTH);
        }

        return new self($value);
    }

    public static function reconstitute(string $value): self
    {
        return new self($value);
    }
}
