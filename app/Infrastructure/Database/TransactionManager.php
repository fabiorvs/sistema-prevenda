<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use CodeIgniter\Database\ConnectionInterface;

final class TransactionManager
{
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function run(callable $operation): void
    {
        $this->connection->transStart();

        try {
            $operation();
        } finally {
            $this->connection->transComplete();
        }

        if (! $this->connection->transStatus()) {
            throw new \RuntimeException('Não foi possível concluir a operação.');
        }
    }
}
