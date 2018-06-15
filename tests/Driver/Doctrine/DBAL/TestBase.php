<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFactory;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Extension\Core;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures\DBALDriverExtension;
use FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures\TestConnectionRegistry;
use PHPUnit\Framework\TestCase;

abstract class TestBase extends TestCase
{
    protected const TABLE_CATEGORY_NAME = 'category';
    protected const TABLE_NEWS_NAME = 'news';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DBALDriverExtension
     */
    protected $testDoctrineExtension;

    protected function getDriverFactory(): DriverFactoryInterface
    {
        $this->testDoctrineExtension = new DBALDriverExtension();

        return new DBALFactory(new TestConnectionRegistry($this->getMemoryConnection()), [
            new CoreExtension(),
            $this->testDoctrineExtension,
        ]);
    }

    protected function getMemoryConnection(): Connection
    {
        if (null === $this->connection) {
            $this->connection = DriverManager::getConnection([
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ]);
        }

        return $this->connection;
    }

    protected function getDataSourceFactory(): DataSourceFactory
    {
        $driverFactoryManager = new DriverFactoryManager([
            $this->getDriverFactory()
        ]);

        $extensions = [
            new Core\Pagination\PaginationExtension(),
            new OrderingExtension(),
        ];

        return new DataSourceFactory($driverFactoryManager, $extensions);
    }

    protected function loadTestData(Connection $connection): void
    {
        $schemaManager = $connection->getSchemaManager();

        $schemaManager->createTable(new Table(self::TABLE_CATEGORY_NAME, [
            new Column('id', Type::getType(Type::INTEGER)),
            new Column('type', Type::getType(Type::STRING)),
            new Column('name', Type::getType(Type::STRING)),
        ]));

        $schemaManager->createTable(new Table(self::TABLE_NEWS_NAME, [
            new Column('id', Type::getType(Type::INTEGER)),
            new Column('visible', Type::getType(Type::BOOLEAN)),
            new Column('title', Type::getType(Type::STRING)),
            new Column('create_datetime', Type::getType(Type::DATETIME)),
            new Column('event_date', Type::getType(Type::DATE)),
            new Column('event_hour', Type::getType(Type::TIME)),
            new Column('content', Type::getType(Type::TEXT)),
            new Column('category_id', Type::getType(Type::INTEGER)),
        ]));

        for ($i=1; $i<=10; $i++) {
            $connection->insert(self::TABLE_CATEGORY_NAME, [
                'id' => $i,
                'type' => $i % 2 == 0 ? 'B' : 'A',
                'name' => sprintf('name-%d', $i),
            ]);
        }

        for ($i=1; $i<=100; $i++) {
            $connection->insert(self::TABLE_NEWS_NAME, [
                'id' => $i,
                'visible' => (int) $i % 2 == 0,
                'title' => sprintf('title-%d', $i),
                'create_datetime' => new \DateTime('@' . (($i - 1) * 60 * 60)),
                'event_date' => new \DateTime('@' . (($i - 1) * 60 * 60)),
                'event_hour' => new \DateTime('@' . (($i - 1) * 60 * 60)),
                'content' => sprintf('Lorem ipsum %d', $i % 3),
                'category_id' => ceil(log($i + 0.001, 101) * 10),
                /*
                 * category id - how many news
                 *  1 - 1
                 *  2 - 1
                 *  3 - 1
                 *  4 - 3
                 *  5 - 4
                 *  6 - 5
                 *  7 - 10
                 *  8 - 15
                 *  9 - 23
                 * 10 - 37
                 */
            ], [
                Type::INTEGER,
                Type::BOOLEAN,
                Type::STRING,
                Type::DATETIME,
                Type::DATE,
                Type::TIME,
                Type::TEXT,
                Type::INTEGER,
            ]);
        }
    }
}
