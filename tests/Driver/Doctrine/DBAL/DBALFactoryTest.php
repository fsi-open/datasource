<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL;

use Doctrine\Common\Persistence\ConnectionRegistry;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFactory;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures\TestConnectionRegistry;

class DBALFactoryTest extends TestBase
{
    /**
     * @var ConnectionRegistry
     */
    private $connectionRegistry;

    /**
     * @var DriverFactoryInterface
     */
    private $factory;

    protected function setUp()
    {
        $this->connectionRegistry = new TestConnectionRegistry($this->getMemoryConnection());
        $this->factory = new DBALFactory($this->connectionRegistry, array());
    }

    public function testDriverType()
    {
        $this->assertEquals('doctrine-dbal', $this->factory->getDriverType());
    }

    public function testExceptionWhenNoTableAndBuilder()
    {
        $this->setExpectedException('Symfony\Component\OptionsResolver\Exception\InvalidOptionsException');
        $this->factory->createDriver(array());
    }

    public function testTableOption()
    {
        $driver = $this->factory->createDriver(array('table' => 'table_name'));
        $this->assertInstanceOf('FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver', $driver);
    }

    public function testQueryBuilderOption()
    {
        $qb = $this->getMemoryConnection()->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_CATEGORY_NAME, 'c');

        $driver = $this->factory->createDriver(array('qb' => $qb));
        $this->assertInstanceOf('FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver', $driver);
    }

    public function testInvalidConnection()
    {
        $driver = $this->factory->createDriver(array(
            'table' => 'table_name',
            'connection' => 'test',
        ));
        $this->assertInstanceOf('FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver', $driver);
    }

    public function testPassExtensions()
    {
        $this->assertCount(0, $this->factory->createDriver(array('table' => 'table_name'))->getExtensions());

        $factory = new DBALFactory($this->connectionRegistry, array(
            new CoreExtension(),
        ));
        $this->assertCount(1, $factory->createDriver(array('table' => 'table_name'))->getExtensions());
    }

    public function testPassIndexField()
    {
        $driver = $this->factory->createDriver(array(
            'table' => self::TABLE_CATEGORY_NAME,
            'indexField' => 'test',
        ));

        $this->assertEquals('test', $driver->getIndexField());

        $driver = $this->factory->createDriver(array(
            'table' => self::TABLE_CATEGORY_NAME,
            'indexField' => function () {},
        ));

        $this->assertInstanceOf('\Closure', $driver->getIndexField());
    }
}
