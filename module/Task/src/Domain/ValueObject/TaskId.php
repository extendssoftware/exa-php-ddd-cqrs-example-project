<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Task\Domain\ValueObject;

use InvalidArgumentException;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Ramsey\Uuid\Uuid;

final readonly class TaskId
{
    private function __construct(private(set) string $value) {}

    public static function create(): self
    {
        return new self(Uuid::uuid7()->toString());
    }

    /**
     * @throws InvalidArgumentException When the value is not a valid UUID version 7.
     */
    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException('Task ID must be a valid UUID version 7.');
        }

        $uuid = Uuid::fromString($value);
        $fields = $uuid->getFields();
        if (!$fields instanceof FieldsInterface || $fields->getVersion() !== Uuid::UUID_TYPE_UNIX_TIME) {
            throw new InvalidArgumentException('Task ID must be a valid UUID version 7.');
        }

        return new self($uuid->toString());
    }
}
