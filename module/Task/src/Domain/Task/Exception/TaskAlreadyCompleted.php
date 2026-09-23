<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception;

use DomainException;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;

use function sprintf;

final class TaskAlreadyCompleted extends DomainException
{
    public function __construct(public readonly TaskId $taskId)
    {
        parent::__construct(sprintf('Task "%s" is already completed.', $taskId->value));
    }
}
