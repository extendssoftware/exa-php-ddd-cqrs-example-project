<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception;

use RuntimeException;

final class TaskAlreadyExists extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Task already exists.');
    }
}
