<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests;

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use PHPUnit\Framework\TestCase;
use FSi\Component\DataSource\DataSourceExtensionInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\Exception\DataSourceException;

/**
 * Tests for DataSourceFactory.
 */
class FactoryTest extends TestCase
{
    /**
     * Checks proper extensions loading.
     */
    public function testExtensionsLoading(): void
    {
        $extension1 = $this->createMock(DataSourceExtensionInterface::class);
        $extension1->method('loadDriverExtensions')->willReturn([]);

        $extension2 = $this->createMock(DataSourceExtensionInterface::class);
        $extension2->method('loadDriverExtensions')->willReturn([]);

        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory()]);

        $extensions = [$extension1, $extension2];

        $factory = new DataSourceFactory($driveFactoryManager, $extensions);
        $datasource = $factory->createDataSource('collection', ['collection' => []]);

        $factoryExtensions = $factory->getExtensions();
        $datasourceExtensions = $datasource->getExtensions();

        self::assertCount(count($factoryExtensions), $extensions);
        self::assertCount(count($datasourceExtensions), $extensions);
    }

    /**
     * Checks exception thrown when loading improper extensions.
     */
    public function testFactoryException2(): void
    {
        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory()]);
        $this->expectException(DataSourceException::class);
        new DataSourceFactory($driveFactoryManager, [new \stdClass()]);
    }

    /**
     * Checks exception thrown when loading scalars in place of extensions.
     */
    public function testFactoryException3(): void
    {
        $this->expectException(DataSourceException::class);

        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory()]);
        new DataSourceFactory($driveFactoryManager, ['scalar']);
    }

    /**
     * Checks exception thrown when creating DataSource with non-existing driver
     */
    public function testFactoryException6(): void
    {
        $this->expectException(DataSourceException::class);
        $this->expectExceptionMessage('Driver "unknownDriver" doesn\'t exist.');

        $factory = new DataSourceFactory(new DriverFactoryManager());
        $factory->createDataSource('unknownDriver');
    }

    /**
     * Checks exception thrown when creating DataSource with non unique name.
     */
    public function testFactoryCreateDataSourceException1(): void
    {
        $this->expectException(DataSourceException::class);

        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory()]);
        $factory = new DataSourceFactory($driveFactoryManager);

        $factory->createDataSource('collection', ['collection' => []], 'unique');
        $factory->createDataSource('collection', ['collection' => []], 'nonunique');
        $factory->createDataSource('collection', ['collection' => []], 'nonunique');
    }

    /**
     * Checks exception thrown when creating DataSource with wrong name.
     */
    public function testFactoryCreateDataSourceException2(): void
    {
        $this->expectException(DataSourceException::class);

        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory()]);
        $factory = new DataSourceFactory($driveFactoryManager);
        $factory->createDataSource('collection', ['collection' => []], 'wrong-one');
    }

    /**
     * Checks exception thrown when creating DataSource with empty name.
     */
    public function testFactoryCreateDataSourceException3(): void
    {
        $this->expectException(DataSourceException::class);

        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory()]);
        $factory = new DataSourceFactory($driveFactoryManager);
        $factory->createDataSource('collection', ['collection' => []], '');
    }

    /**
     * Checks adding DataSoucre to factory.
     */
    public function testAddDataSource(): void
    {
        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory()]);
        $factory = new DataSourceFactory($driveFactoryManager);

        $driver = $this->createMock(DriverInterface::class);
        $datasource = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();
        $datasource->method('getName')->willReturn('name');
        $datasource->expects(self::atLeastOnce())->method('setFactory')->with($factory);

        $datasource2 = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();
        $datasource2->method('getName')->willReturn('name');

        $factory->addDataSource($datasource);
        // Check if adding it twice won't cause exception.
        $factory->addDataSource($datasource);

        // Checking exception for adding different datasource with the same name.
        $this->expectException(DataSourceException::class);
        $factory->addDataSource($datasource2);
    }

    /**
     * Checks fetching parameters of all and others datasources.
     */
    public function testGetAllAndOtherParameters(): void
    {
        $driveFactoryManager = new DriverFactoryManager([new CollectionFactory()]);
        $factory = new DataSourceFactory($driveFactoryManager);

        $driver = $this->createMock(DriverInterface::class);

        $params1 = [
            'key1' => 'value1',
        ];
        $datasource1 = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();
        $datasource1->method('getName')->willReturn('name');
        $datasource1->method('getParameters')->willReturn($params1);

        $params2 = [
            'key2' => 'value2',
        ];
        $datasource2 = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();
        $datasource2->method('getName')->willReturn('name2');
        $datasource2->method('getParameters')->willReturn($params2);

        $result = array_merge($params1, $params2);

        $factory->addDataSource($datasource1);
        $factory->addDataSource($datasource2);

        self::assertEquals($factory->getOtherParameters($datasource1), $params2);
        self::assertEquals($factory->getOtherParameters($datasource2), $params1);
        self::assertEquals($factory->getAllParameters(), $result);
    }
}
