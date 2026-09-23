<?php

declare(strict_types=1);

namespace Domain\Task;

use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;

final class TaskStatusTest extends TestCase
{
    /**
     * @return array<string, array{string, TaskStatus}>
     */
    public static function statuses(): array
    {
        return [
            'pending' => ['pending', TaskStatus::Pending],
            'in progress' => ['in_progress', TaskStatus::InProgress],
            'completed' => ['completed', TaskStatus::Completed],
        ];
    }

    #[Test]
    #[DataProvider('statuses')]
    public function fromRestoresMatchingCase(string $value, TaskStatus $expected): void
    {
        self::assertSame($expected, TaskStatus::from($value));
        self::assertSame($value, $expected->value);
    }

    #[Test]
    #[DataProvider('statuses')]
    public function tryFromReturnsMatchingCase(string $value, TaskStatus $expected): void
    {
        self::assertSame($expected, TaskStatus::tryFrom($value));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidStatuses(): array
    {
        return [
            'empty' => [''],
            'unknown' => ['cancelled'],
            'uppercase' => ['PENDING'],
            'whitespace' => [' pending '],
        ];
    }

    #[Test]
    #[DataProvider('invalidStatuses')]
    public function fromRejectsUnsupportedStatus(string $value): void
    {
        $this->expectException(ValueError::class);

        /** @noinspection PhpExpressionResultUnusedInspection */
        TaskStatus::from($value);
    }

    #[Test]
    #[DataProvider('invalidStatuses')]
    public function tryFromReturnsNullForUnsupportedStatus(string $value): void
    {
        self::assertNull(TaskStatus::tryFrom($value));
    }
}
