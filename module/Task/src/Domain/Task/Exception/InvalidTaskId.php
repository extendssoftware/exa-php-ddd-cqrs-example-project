<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception;

use InvalidArgumentException;

final class InvalidTaskId extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Task ID must be a valid UUID version 7.');
    }
}
