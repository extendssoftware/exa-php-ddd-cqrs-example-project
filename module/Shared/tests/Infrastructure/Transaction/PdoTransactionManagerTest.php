<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Tests\Infrastructure\Transaction;

use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Transaction\PdoTransactionManager;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PdoTransactionManagerTest extends TestCase
{
    #[Test]
    public function rollsBackWhenCommitFailsAndPropagatesOriginalException(): void
    {
        $failure = new PDOException('Commit failed');
        $operationCompleted = false;
        $pdo = $this->createMock(PDO::class);
        $pdo->expects(self::exactly(2))->method('inTransaction')->willReturn(false, true);
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::once())->method('commit')->willReturnCallback(
            static function () use (&$operationCompleted, $failure): never {
                self::assertTrue($operationCompleted);
                throw $failure;
            },
        );
        $pdo->expects(self::once())->method('rollBack')->willReturn(true);
        $manager = new PdoTransactionManager($pdo);

        try {
            $manager->transactional(static function () use (&$operationCompleted): void {
                $operationCompleted = true;
            });
            self::fail('The commit failure must propagate.');
        } catch (PDOException $exception) {
            self::assertSame($failure, $exception);
        }
    }
}
