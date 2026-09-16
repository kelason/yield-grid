<?php

declare(strict_types=1);

namespace App\Domain\Shared\Database;

interface TransactionManagerInterface
{
    /**
     * Execute a callback within a database transaction.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
