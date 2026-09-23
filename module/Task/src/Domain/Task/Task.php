<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotCompleted;

final class Task
{
    private function __construct(
        readonly TaskId $id,
        private(set) TaskTitle $title,
        private(set) TaskStatus $status,
        private(set) ?DateTimeImmutable $completedAt,
    ) {}

    public static function create(TaskId $id, TaskTitle $title): self
    {
        return new self($id, $title, TaskStatus::Pending, null);
    }

    public static function reconstitute(
        TaskId $id,
        TaskTitle $title,
        TaskStatus $status,
        ?DateTimeImmutable $completedAt,
    ): self {
        return new self($id, $title, $status, $completedAt);
    }

    public function rename(TaskTitle $title): void
    {
        $this->title = $title;
    }

    /**
     * @throws TaskNotCompleted When the task is not completed.
     */
    public function reopen(): void
    {
        if ($this->status !== TaskStatus::Completed) {
            throw new TaskNotCompleted();
        }

        $this->status = TaskStatus::Pending;
        $this->completedAt = null;
    }

    /**
     * @throws TaskAlreadyCompleted When the task is already completed.
     */
    public function complete(DateTimeImmutable $completedAt): void
    {
        if ($this->status === TaskStatus::Completed) {
            throw new TaskAlreadyCompleted();
        }

        $this->status = TaskStatus::Completed;
        $this->completedAt = $completedAt;
    }
}
