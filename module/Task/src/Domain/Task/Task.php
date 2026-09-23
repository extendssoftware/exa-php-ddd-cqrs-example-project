<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\Task;

use DateTimeImmutable;
use ExtendsSoftware\ExaPHPExample\Shared\Domain\AbstractAggregateRoot;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCreated;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskRenamed;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskReopened;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotCompleted;

final class Task extends AbstractAggregateRoot
{
    private function __construct(
        readonly TaskId $id,
        private(set) TaskTitle $title,
        private(set) TaskStatus $status,
        private(set) ?DateTimeImmutable $completedAt,
    ) {}

    public static function create(TaskId $id, TaskTitle $title): self
    {
        $task = new self($id, $title, TaskStatus::Pending, null);
        $task->recordThat(new TaskCreated($id, $title));

        return $task;
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
        if ($this->title->value === $title->value) {
            return;
        }

        $this->title = $title;
        $this->recordThat(new TaskRenamed($this->id, $title));
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
        $this->recordThat(new TaskReopened($this->id));
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
        $this->recordThat(new TaskCompleted($this->id, $completedAt));
    }
}
