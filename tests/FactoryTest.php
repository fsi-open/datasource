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
    public function testExtensionsLoading()
    {
        $extension1 = $this->createMock(DataSourceExtensionInterface::class);
        $extension2 = $this->createMock(DataSourceExtensionInterface::class);

        $extension1
            ->expects($this->any())
            ->method('loadDriverExtensions')
            ->willReturn([])
        ;

        $extension2
            ->expects($this->any())
            ->method('loadDriverExtensions')
            ->willReturn([])
        ;

        $driveFactoryManager = new DriverFactoryManager([
            new CollectionFactory()
        ]);

        $extensions = [$extension1, $extension2];

        $factory = new DataSourceFactory($driveFactoryManager, $extensions);
        $datasource = $factory->createDataSource('collection', ['collection' => []]);

        $factoryExtensions = $factory->getExtensions();
        $datasourceExtensions = $datasource->getExtensions();

        $this->assertCount(count($factoryExtensions), $extensions);
        $this->assertCount(count($datasourceExtensions), $extensions);
    }

    /**
     * Checks exception thrown when loading inproper extensions.
     *
     * @expectedException \FSi\Component\DataSource\Exception\DataSourceException
     */
    public function testFactoryException2()
    {
        $driveFactoryManager = new DriverFactoryManager([
            new CollectionFactory()
        ]);
        new DataSourceFactory($driveFactoryManager, [new \stdClass()]);
    }

    /**
     * Checks exception thrown when loading scalars in place of extensions.
     */
    public function testFactoryException3()
    {
        $this->expectException(DataSourceException::class);

        $driveFactoryManager = new DriverFactoryManager([
            new CollectionFactory()
        ]);
        new DataSourceFactory($driveFactoryManager, ['scalar']);
    }

    /**
     * Checks exception thrown when creating DataSource with non-existing driver
     */
    public function testFactoryException6()
    {
        $this->expectException(DataSourceException::class);
        $this->expectExceptionMessage('Driver "unknownDriver" doesn\'t exist.');

        $factory = new DataSourceFactory(new DriverFactoryManager());
        $factory->createDataSource('unknownDriver');
    }

    /**
     * Checks exception thrown when creating DataSource with non unique name.
     */
    public function testFactoryCreateDataSourceException1()
    {
        $this->expectException(DataSourceException::class);

        $driveFactoryManager = new DriverFactoryManager([
            new CollectionFactory()
        ]);
        $factory = new DataSourceFactory($driveFactoryManager);

        $factory->createDataSource('collection', ['collection' => []], 'unique');
        $factory->createDataSource('collection', ['collection' => []], 'nonunique');
        $factory->createDataSource('collection', ['collection' => []], 'nonunique');
    }

    /**
     * Checks exception thrown when creating DataSource with wrong name.
     */
    public function testFactoryCreateDataSourceException2()
    {
        $this->expectException(DataSourceException::class);

        $driveFactoryManager = new DriverFactoryManager([
            new CollectionFactory()
        ]);
        $factory = new DataSourceFactory($driveFactoryManager);
        $factory->createDataSource('collection', ['collection' => []], 'wrong-one');
    }

    /**
     * Checks exception thrown when creating DataSource with empty name.
     */
    public function testFactoryCreateDataSourceException3()
    {
        $this->expectException(DataSourceException::class);

        $driveFactoryManager = new DriverFactoryManager([
            new CollectionFactory()
        ]);
        $factory = new DataSourceFactory($driveFactoryManager);
        $factory->createDataSource('collection', ['collection' => []], '');
    }

    /**
     * Checks adding DataSoucre to factory.
     */
    public function testAddDataSource()
    {
        $driveFactoryManager = new DriverFactoryManager([
            new CollectionFactory()
        ]);
        $factory = new DataSourceFactory($driveFactoryManager);

        $driver = $this->createMock(DriverInterface::class);
        $datasource = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();
        $datasource2 = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();

        $datasource->expects($this->any())
            ->method('getName')
            ->willReturn('name');

        $datasource2->expects($this->any())
            ->method('getName')
            ->willReturn('name');

        $datasource->expects($this->atLeastOnce())
            ->method('setFactory')
            ->with($factory);

        $factory->addDataSource($datasource);
        //Check if adding it twice won't cause exception.
        $factory->addDataSource($datasource);

        //Checking exception for adding different datasource with the same name.
        $this->expectException(DataSourceException::class);
        $factory->addDataSource($datasource2);
    }

    /**
     * Checks fetching parameters of all and others datasources.
     */
    public function testGetAllAndOtherParameters()
    {
        $driveFactoryManager = new DriverFactoryManager([
            new CollectionFactory()
        ]);
        $factory = new DataSourceFactory($driveFactoryManager);

        $driver = $this->createMock(DriverInterface::class);
        $datasource1 = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();
        $datasource2 = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();

        $params1 = [
            'key1' => 'value1',
        ];

        $params2 = [
            'key2' => 'value2',
        ];

        $result = array_merge($params1, $params2);

        $datasource1->expects($this->any())
            ->method('getName')
            ->willReturn('name');

        $datasource2->expects($this->any())
            ->method('getName')
            ->willReturn('name2');

        $datasource1->expects($this->any())
            ->method('getParameters')
            ->willReturn($params1);

        $datasource2->expects($this->any())
            ->method('getParameters')
            ->willReturn($params2);

        $factory->addDataSource($datasource1);
        $factory->addDataSource($datasource2);

        $this->assertEquals($factory->getOtherParameters($datasource1), $params2);
        $this->assertEquals($factory->getOtherParameters($datasource2), $params1);
        $this->assertEquals($factory->getAllParameters(), $result);
    }
}
