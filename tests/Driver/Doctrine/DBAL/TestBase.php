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
use FSi\Component\DataSource\Extension\Symfony;
use FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures\DBALDriverExtension;
use FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures\TestConnectionRegistry;

abstract class TestBase extends \PHPUnit_Framework_TestCase
{
    const TABLE_CATEGORY_NAME = 'category';
    const TABLE_NEWS_NAME = 'news';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DBALDriverExtension
     */
    protected $testDoctrineExtension;

    /**
     * @return DriverFactoryInterface
     */
    protected function getDriverFactory()
    {
        $this->testDoctrineExtension = new DBALDriverExtension();

        return new DBALFactory(new TestConnectionRegistry($this->getMemoryConnection()), array(
            new CoreExtension(),
            $this->testDoctrineExtension,
        ));
    }

    /**
     * @return Connection
     */
    protected function getMemoryConnection()
    {
        if (empty($this->connection)) {
            $this->connection = DriverManager::getConnection(array(
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ));
        }

        return $this->connection;
    }

    /**
     * @return DataSourceFactory
     */
    protected function getDataSourceFactory()
    {
        $driverFactoryManager = new DriverFactoryManager(array(
            $this->getDriverFactory()
        ));

        $extensions = array(
            new Symfony\Core\CoreExtension(),
            new Core\Pagination\PaginationExtension(),
            new OrderingExtension(),
        );

        return new DataSourceFactory($driverFactoryManager, $extensions);
    }

    protected function loadTestData(Connection $connection)
    {
        $schemaManager = $connection->getSchemaManager();

        $schemaManager->createTable(new Table(self::TABLE_CATEGORY_NAME, array(
            new Column('id', Type::getType(Type::INTEGER)),
            new Column('type', Type::getType(Type::STRING)),
            new Column('name', Type::getType(Type::STRING)),
        )));

        $schemaManager->createTable(new Table(self::TABLE_NEWS_NAME, array(
            new Column('id', Type::getType(Type::INTEGER)),
            new Column('visible', Type::getType(Type::INTEGER)),
            new Column('title', Type::getType(Type::STRING)),
            new Column('create_date', Type::getType(Type::DATETIME)),
            new Column('content', Type::getType(Type::TEXT)),
            new Column('category_id', Type::getType(Type::INTEGER)),
        )));

        for ($i=1; $i<=10; $i++) {
            $connection->insert(self::TABLE_CATEGORY_NAME, array(
                'id' => $i,
                'type' => $i % 2 == 0 ? 'B' : 'A',
                'name' => sprintf('name-%d', $i),
            ));
        }

        for ($i=1; $i<=100; $i++) {
            $connection->insert(self::TABLE_NEWS_NAME, array(
                'id' => $i,
                'visible' => (int) $i % 2 == 0,
                'title' => sprintf('title-%d', $i),
                'create_date' => date("Y-m-d H:i:s", ($i - 1) * 60 * 60 - 60 * 60),
                'content' => sprintf('Lorem ipsum %d', $i % 3),
                'category_id' => ceil((log($i - 1, 100) + 0.001) * 10),
                /*
                 * category id - how many news
                 *  1 - 1
                 *  2 - 1
                 *  3 - 1
                 *  4 - 3
                 *  5 - 3
                 *  6 - 6
                 *  7 - 10
                 *  8 - 14
                 *  9 - 23
                 * 10 - 37
                 */
            ));
        }
    }
}
