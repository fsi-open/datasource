<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures;

use Doctrine\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Driver\Connection;

class TestConnectionRegistry implements ConnectionRegistry
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getDefaultConnectionName(): string
    {
        return 'test';
    }

    public function getConnection($name = null): ?Connection
    {
        if ($name !== $this->getDefaultConnectionName()) {
            throw new \InvalidArgumentException('invalid connection');
        }

        return $this->connection;
    }

    public function getConnections(): array
    {
        return [$this->connection];
    }

    public function getConnectionNames(): array
    {
        return [$this->getDefaultConnectionName()];
    }
}
