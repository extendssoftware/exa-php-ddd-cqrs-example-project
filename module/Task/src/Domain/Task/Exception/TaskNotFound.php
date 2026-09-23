<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception;

use RuntimeException;

final class TaskNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Task was not found.');
    }
}
