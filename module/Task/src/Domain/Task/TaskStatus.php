<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task;

enum TaskStatus: string
{
    case Open = 'open';
    case Completed = 'completed';
}
