<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Tests\Domain\ValueObject;

use Error;
use ExtendsSoftware\ExaPHPExample\Task\Domain\ValueObject\TaskId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TaskIdTest extends TestCase
{
    #[Test]
    public function createGeneratesDistinctUuidVersionSevenValues(): void
    {
        $first = TaskId::create();
        $second = TaskId::create();

        foreach ([$first, $second] as $id) {
            self::assertMatchesRegularExpression(
                '/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/',
                $id->value,
            );
        }

        self::assertNotSame($first->value, $second->value);
    }

    #[Test]
    public function fromStringRestoresExistingUuidVersionSeven(): void
    {
        $value = '01902424-9b00-7cc3-98c4-2c1f7c675ced';

        self::assertSame($value, TaskId::fromString($value)->value);
    }

    #[Test]
    public function fromStringNormalizesUppercaseInput(): void
    {
        $id = TaskId::fromString('01902424-9B00-7CC3-98C4-2C1F7C675CED');

        self::assertSame('01902424-9b00-7cc3-98c4-2c1f7c675ced', $id->value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidValues(): array
    {
        return [
            'empty' => [''],
            'malformed' => ['not-a-uuid'],
            'invalid hexadecimal' => ['01902424-9b00-7cc3-98c4-2c1f7c675ceg'],
            'version four' => ['550e8400-e29b-41d4-a716-446655440000'],
            'version six' => ['01902424-9b00-6cc3-98c4-2c1f7c675ced'],
            'version eight' => ['01902424-9b00-8cc3-98c4-2c1f7c675ced'],
            'invalid variant' => ['01902424-9b00-7cc3-18c4-2c1f7c675ced'],
            'nil' => ['00000000-0000-0000-0000-000000000000'],
            'max' => ['ffffffff-ffff-ffff-ffff-ffffffffffff'],
        ];
    }

    #[Test]
    #[DataProvider('invalidValues')]
    public function fromStringRejectsInvalidTaskIds(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Task ID must be a valid UUID version 7.');

        TaskId::fromString($value);
    }

    #[Test]
    public function valueCannotBeChanged(): void
    {
        $id = TaskId::fromString('01902424-9b00-7cc3-98c4-2c1f7c675ced');

        $this->expectException(Error::class);

        /** @noinspection PhpCannotModifyPropertyOutsideSetVisibilityScopeInspection */
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $id->value = '01902424-9b00-7cc3-98c4-2c1f7c675cee';
    }
}
