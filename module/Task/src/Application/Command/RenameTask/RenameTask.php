<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Command\RenameTask;

final readonly class RenameTask
{
    public function __construct(public string $taskId, public string $title) {}
}
