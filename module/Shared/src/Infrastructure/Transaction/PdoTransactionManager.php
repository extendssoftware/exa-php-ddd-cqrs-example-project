<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Transaction;

use ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction\TransactionManagerInterface;
use LogicException;
use PDO;
use PDOException;
use Throwable;

final readonly class PdoTransactionManager implements TransactionManagerInterface
{
    public function __construct(private PDO $pdo) {}

    /**
     * @inheritDoc
     * @throws PDOException When starting or committing the transaction fails.
     */
    public function transactional(callable $operation): mixed
    {
        if ($this->pdo->inTransaction()) {
            throw new LogicException('A transaction is already active.');
        }

        $this->pdo->beginTransaction();

        try {
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            try {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            } catch (PDOException) {
                // Preserve the original failure when the connection can no longer roll back.
            }

            throw $exception;
        }
    }
}
