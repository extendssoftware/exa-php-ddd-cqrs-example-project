<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask;

final readonly class GetTask
{
    public function __construct(public string $taskId) {}
}
