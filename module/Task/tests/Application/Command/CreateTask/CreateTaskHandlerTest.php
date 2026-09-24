<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Application\Command\CreateTask;

use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTask;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\TaskAlreadyExists;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Task;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskId;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

final class CreateTaskHandlerTest extends TestCase
{
    private const string TASK_ID = '01902424-9b00-7cc3-98c4-2c1f7c675ced';

    #[Test]
    public function invokeAddsPendingTaskWithSuppliedIdAndTitle(): void
    {
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('add')
            ->with(
                self::callback(
                    static function (Task $task): bool {
                        $state = $task->state();
                        self::assertSame(self::TASK_ID, $state->id->value);
                        self::assertSame('  Example task  ', $state->title->value);
                        self::assertSame(TaskStatus::Pending, $state->status);
                        self::assertNull($state->completedAt);

                        return true;
                    },
                ),
            );
        $handler = new CreateTaskHandler($repository);

        $handler(new CreateTask(self::TASK_ID, '  Example task  '));
    }

    /**
     * @return array<string, array{string, string, class-string<InvalidTaskId|InvalidTaskTitle>}>
     */
    public static function invalidCommands(): array
    {
        return [
            'malformed ID' => ['invalid', 'Example task', InvalidTaskId::class],
            'wrong UUID version' => ['550e8400-e29b-41d4-a716-446655440000', 'Example task', InvalidTaskId::class],
            'short title' => [self::TASK_ID, 'ab', InvalidTaskTitle::class],
            'long title' => [self::TASK_ID, str_repeat('a', 101), InvalidTaskTitle::class],
            'invalid title encoding' => [self::TASK_ID, "abc\xFF", InvalidTaskTitle::class],
        ];
    }

    /**
     * @param class-string<InvalidTaskId|InvalidTaskTitle> $exception
     */
    #[Test]
    #[DataProvider('invalidCommands')]
    public function invokeRejectsInvalidInputWithoutWriting(string $taskId, string $title, string $exception): void
    {
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository
            ->expects(self::never())
            ->method('add');
        $handler = new CreateTaskHandler($repository);

        $this->expectException($exception);

        $handler(new CreateTask($taskId, $title));
    }

    #[Test]
    public function invokePropagatesRepositoryFailure(): void
    {
        $exception = new TaskAlreadyExists(TaskId::fromString(self::TASK_ID));
        $repository = $this->createMock(TaskRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('add')
            ->willThrowException($exception);
        $handler = new CreateTaskHandler($repository);

        $this->expectExceptionObject($exception);

        $handler(new CreateTask(self::TASK_ID, 'Example task'));
    }
}
