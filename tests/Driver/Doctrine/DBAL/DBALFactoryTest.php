<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL;

use Closure;
use Doctrine\Persistence\ConnectionRegistry;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFactory;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures\TestConnectionRegistry;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

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

    protected function setUp(): void
    {
        $this->connectionRegistry = new TestConnectionRegistry($this->getMemoryConnection());
        $this->factory = new DBALFactory($this->connectionRegistry, []);
    }

    public function testDriverType(): void
    {
        self::assertEquals('doctrine-dbal', $this->factory->getDriverType());
    }

    public function testExceptionWhenNoTableAndBuilder(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->factory->createDriver([]);
    }

    public function testTableOption(): void
    {
        $driver = $this->factory->createDriver(['table' => 'table_name']);
        self::assertInstanceOf(DBALDriver::class, $driver);
    }

    public function testQueryBuilderOption(): void
    {
        $qb = $this->getMemoryConnection()->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_CATEGORY_NAME, 'c');

        $driver = $this->factory->createDriver(['qb' => $qb]);
        self::assertInstanceOf(DBALDriver::class, $driver);
    }

    public function testInvalidConnection(): void
    {
        $driver = $this->factory->createDriver([
            'table' => 'table_name',
            'connection' => 'test',
        ]);
        self::assertInstanceOf(DBALDriver::class, $driver);
    }

    public function testPassExtensions(): void
    {
        self::assertCount(0, $this->factory->createDriver(['table' => 'table_name'])->getExtensions());

        $factory = new DBALFactory($this->connectionRegistry, [
            new CoreExtension(),
        ]);
        self::assertCount(1, $factory->createDriver(['table' => 'table_name'])->getExtensions());
    }

    public function testPassIndexField(): void
    {
        $driver = $this->factory->createDriver([
            'table' => self::TABLE_CATEGORY_NAME,
            'indexField' => 'test',
        ]);
        self::assertInstanceOf(DBALDriver::class, $driver);
        self::assertEquals('test', $driver->getIndexField());

        $driver = $this->factory->createDriver([
            'table' => self::TABLE_CATEGORY_NAME,
            'indexField' => static function () {
            },
        ]);
        self::assertInstanceOf(DBALDriver::class, $driver);

        self::assertInstanceOf(Closure::class, $driver->getIndexField());
    }
}
