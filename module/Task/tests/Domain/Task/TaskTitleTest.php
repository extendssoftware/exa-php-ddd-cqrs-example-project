<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Domain\Task;

use Error;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Exception\InvalidTaskTitle;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

final class TaskTitleTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function validTitles(): array
    {
        return [
            'minimum length' => ['abc'],
            'maximum length' => [str_repeat('a', 100)],
            'ordinary title' => ['Complete the task'],
            'preserves whitespace' => ['  Complete the task  '],
            'multibyte minimum' => ['任务名'],
            'multibyte maximum' => [str_repeat('é', 100)],
            'emoji' => ['😀😀😀'],
        ];
    }

    #[Test]
    #[DataProvider('validTitles')]
    public function fromStringPreservesValidTitle(string $value): void
    {
        self::assertSame($value, TaskTitle::fromString($value)->value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidTitles(): array
    {
        return [
            'empty' => [''],
            'below minimum' => ['ab'],
            'above maximum' => [str_repeat('a', 101)],
            'multibyte below minimum' => ['任务'],
            'multibyte above maximum' => [str_repeat('é', 101)],
        ];
    }

    #[Test]
    #[DataProvider('invalidTitles')]
    public function fromStringRejectsInvalidTitle(string $value): void
    {
        $this->expectException(InvalidTaskTitle::class);
        $this->expectExceptionMessageIsOrContains(
            'Task title must contain between 3 and 100 characters.',
        );

        TaskTitle::fromString($value);
    }

    #[Test]
    public function fromStringRejectsInvalidEncoding(): void
    {
        $this->expectException(InvalidTaskTitle::class);
        $this->expectExceptionMessageIsOrContains('Task title must be valid UTF-8.');

        TaskTitle::fromString("abc\xFF");
    }

    #[Test]
    public function reconstitutePreservesInvalidEncoding(): void
    {
        self::assertSame("abc\xFF", TaskTitle::reconstitute("abc\xFF")->value);
    }

    #[Test]
    #[DataProvider('invalidTitles')]
    public function reconstitutePreservesValuesWithoutCreationValidation(string $value): void
    {
        self::assertSame($value, TaskTitle::reconstitute($value)->value);
    }

    #[Test]
    public function valueCannotBeChanged(): void
    {
        $title = TaskTitle::fromString('Complete the task');

        $this->expectException(Error::class);

        /** @noinspection PhpCannotModifyPropertyOutsideSetVisibilityScopeInspection */
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $title->value = 'Another task';
    }
}
