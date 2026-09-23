<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception;

use DomainException;

final class TaskNotCompleted extends DomainException
{
    public function __construct()
    {
        parent::__construct('Task is not completed.');
    }
}
