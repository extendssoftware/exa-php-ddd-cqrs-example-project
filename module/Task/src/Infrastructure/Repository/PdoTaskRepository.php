<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Repository;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskNotFound;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskState;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use PDO;
use PDOException;

final readonly class PdoTaskRepository implements TaskRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    /**
     * @throws PDOException When the database read fails.
     * @throws InvalidTaskId When the stored ID is not a UUID version 7.
     * @throws DateMalformedStringException When a stored creation or completion time is invalid.
     */
    public function find(TaskId $id): ?Task
    {
        $statement = $this->pdo->prepare(
            'SELECT BIN_TO_UUID(id) AS id, title, status, created_at, completed_at FROM task WHERE id = UUID_TO_BIN(:id)',
        );
        $statement->execute(['id' => $id->value]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return Task::reconstitute(
            new TaskState(
                TaskId::fromString($row['id']),
                TaskTitle::reconstitute($row['title']),
                TaskStatus::from($row['status']),
                $this->fromDatabaseTimestamp($row['created_at']),
                $this->fromDatabaseTimestamp($row['completed_at']),
            ),
        );
    }

    /**
     * @throws TaskAlreadyExists When a task with the same ID is already stored.
     * @throws PDOException When the database write fails for another reason.
     */
    public function add(Task $task): void
    {
        $state = $task->state();

        $statement = $this->pdo->prepare(
            'INSERT INTO task (id, title, status, created_at, completed_at) VALUES (UUID_TO_BIN(:id), :title, :status, :created_at, :completed_at)',
        );

        try {
            $statement->execute([
                'id' => $state->id->value,
                'title' => $state->title->value,
                'status' => $state->status->value,
                'created_at' => $this->toDatabaseTimestamp($state->createdAt),
                'completed_at' => $this->toDatabaseTimestamp($state->completedAt),
            ]);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new TaskAlreadyExists($state->id);
            }

            throw $exception;
        }
    }

    /**
     * @throws TaskNotFound When no task with this ID is stored.
     * @throws PDOException When the database write or existence check fails.
     */
    public function update(Task $task): void
    {
        $state = $task->state();

        $statement = $this->pdo->prepare(
            'UPDATE task SET title = :title, status = :status, completed_at = :completed_at WHERE id = UUID_TO_BIN(:id)',
        );
        $statement->execute([
            'id' => $state->id->value,
            'title' => $state->title->value,
            'status' => $state->status->value,
            'completed_at' => $this->toDatabaseTimestamp($state->completedAt),
        ]);

        if ($statement->rowCount() === 0) {
            $exists = $this->pdo->prepare('SELECT 1 FROM task WHERE id = UUID_TO_BIN(:id)');
            $exists->execute(['id' => $state->id->value]);

            if ($exists->fetchColumn() === false) {
                throw new TaskNotFound($state->id);
            }
        }
    }

    /**
     * @throws TaskNotFound When no task with this ID is stored.
     * @throws PDOException When the database write fails.
     */
    public function remove(Task $task): void
    {
        $id = $task->state()->id;

        $statement = $this->pdo->prepare('DELETE FROM task WHERE id = UUID_TO_BIN(:id)');
        $statement->execute(['id' => $id->value]);

        if ($statement->rowCount() === 0) {
            throw new TaskNotFound($id);
        }
    }

    private function toDatabaseTimestamp(?DateTimeImmutable $time): ?string
    {
        return $time?->setTimezone(new DateTimeZone('UTC'))
                    ->format('Y-m-d H:i:s.u');
    }

    /**
     * @throws DateMalformedStringException When the stored timestamp is invalid.
     */
    private function fromDatabaseTimestamp(?string $time): ?DateTimeImmutable
    {
        return $time === null ? null : new DateTimeImmutable($time, new DateTimeZone('UTC'));
    }
}
