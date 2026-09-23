<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception;

use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use RuntimeException;

use function sprintf;

final class TaskAlreadyExists extends RuntimeException
{
    public function __construct(public readonly TaskId $taskId)
    {
        parent::__construct(sprintf('Task "%s" already exists.', $taskId->value));
    }
}
