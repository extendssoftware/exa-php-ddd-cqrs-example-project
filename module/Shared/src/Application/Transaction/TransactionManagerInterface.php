<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction;

use LogicException;
use Throwable;

interface TransactionManagerInterface
{
    /**
     * Commits the operation on success and rolls it back on failure.
     * The operation must not manage transactions itself.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws LogicException When a transaction is already active; the operation is not invoked.
     * @throws Throwable When the operation or transaction management fails.
     */
    public function transactional(callable $operation): mixed;
}
