<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Tests\Infrastructure\Transaction;

use Error;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Transaction\PdoTransactionManager;
use LogicException;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Throwable;

#[Group('integration')]
final class PdoTransactionManagerIntegrationTest extends TestCase
{
    private PDO $pdo;
    private PdoTransactionManager $manager;

    protected function setUp(): void
    {
        $config = (require __DIR__ . '/../../../../../config/pdo.local.php.dist')[PDO::class];
        $this->pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
        $this->pdo->exec('CREATE TEMPORARY TABLE transaction_test (id INT PRIMARY KEY) ENGINE = InnoDB');
        $this->manager = new PdoTransactionManager($this->pdo);
    }

    #[Test]
    public function commitsWritesAndReturnsOperationResult(): void
    {
        $expected = new stdClass();
        $result = $this->manager->transactional(function () use ($expected): object {
            self::assertTrue($this->pdo->inTransaction());
            $this->pdo->exec('INSERT INTO transaction_test VALUES (1)');

            return $expected;
        });

        self::assertSame($expected, $result);
        self::assertFalse($this->pdo->inTransaction());
        self::assertSame(1, $this->countRows());
    }

    #[Test]
    public function acceptsVoidOperationsAndCanBeReused(): void
    {
        for ($id = 1; $id <= 2; $id++) {
            self::assertNull($this->manager->transactional(function () use ($id): void {
                $statement = $this->pdo->prepare('INSERT INTO transaction_test VALUES (:id)');
                $statement->execute(['id' => $id]);
            }));
        }

        self::assertSame(2, $this->countRows());
        self::assertFalse($this->pdo->inTransaction());
    }

    /**
     * @return array<string, array{Throwable}>
     */
    public static function failures(): array
    {
        return [
            'exception' => [new RuntimeException('Operation failed')],
            'error' => [new Error('Operation failed')],
        ];
    }

    #[Test]
    #[DataProvider('failures')]
    public function rollsBackAndPreservesOriginalFailure(Throwable $failure): void
    {
        try {
            $this->manager->transactional(function () use ($failure): never {
                $this->pdo->exec('INSERT INTO transaction_test VALUES (1)');
                throw $failure;
            });
            self::fail('The operation failure must propagate.');
        } catch (Throwable $exception) {
            self::assertSame($failure, $exception);
        }

        self::assertFalse($this->pdo->inTransaction());
        self::assertSame(0, $this->countRows());
        $this->manager->transactional(function (): void {
            $this->pdo->exec('INSERT INTO transaction_test VALUES (2)');
        });
        self::assertSame(1, $this->countRows());
    }

    #[Test]
    public function rollsBackEarlierWritesOnDatabaseFailure(): void
    {
        $this->expectException(PDOException::class);

        try {
            $this->manager->transactional(function (): void {
                $this->pdo->exec('INSERT INTO transaction_test VALUES (1)');
                $this->pdo->exec('INSERT INTO transaction_test VALUES (1)');
            });
        } finally {
            self::assertFalse($this->pdo->inTransaction());
            self::assertSame(0, $this->countRows());
        }
    }

    #[Test]
    public function rejectsExistingTransactionWithoutInvokingOperationOrChangingIt(): void
    {
        $this->pdo->beginTransaction();
        $this->pdo->exec('INSERT INTO transaction_test VALUES (1)');
        $called = false;

        try {
            $this->manager->transactional(static function () use (&$called): void {
                $called = true;
            });
            self::fail('An existing transaction must be rejected.');
        } catch (LogicException) {
            self::assertFalse($called);
            self::assertTrue($this->pdo->inTransaction());
            self::assertSame(1, $this->countRows());
        } finally {
            $this->pdo->rollBack();
        }

        self::assertSame(0, $this->countRows());
    }

    #[Test]
    public function nestedTransactionFailureRollsBackOuterOperation(): void
    {
        $this->expectException(LogicException::class);

        try {
            $this->manager->transactional(function (): void {
                $this->pdo->exec('INSERT INTO transaction_test VALUES (1)');
                new PdoTransactionManager($this->pdo)->transactional(static fn(): null => null);
            });
        } finally {
            self::assertFalse($this->pdo->inTransaction());
            self::assertSame(0, $this->countRows());
        }
    }

    private function countRows(): int
    {
        return (int)$this->pdo
            ->query('SELECT COUNT(*) FROM transaction_test')
            ->fetchColumn();
    }
}
