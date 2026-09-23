<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception;

use InvalidArgumentException;

final class InvalidTaskTitle extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Task title must contain between 3 and 100 characters of valid UTF-8.');
    }
}
