<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception;

use InvalidArgumentException;

final class InvalidTaskTitle extends InvalidArgumentException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidEncoding(): self
    {
        return new self('Task title must be valid UTF-8.');
    }

    public static function invalidLength(int $minLength, int $maxLength): self
    {
        return new self(sprintf('Task title must contain between %d and %d characters.', $minLength, $maxLength));
    }
}
