<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests;

use FSi\Component\DataSource\DataSourceFactory;

/**
 * Tests for DataSourceFactory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks proper extensions loading.
     */
    public function testExtensionsLoading()
    {
        $extension1 = $this->getMock('FSi\Component\DataSource\DataSourceExtensionInterface');
        $extension2 = $this->getMock('FSi\Component\DataSource\DataSourceExtensionInterface');

        $extension1
            ->expects($this->any())
            ->method('loadDriverExtensions')
            ->will($this->returnValue(array()))
        ;

        $extension2
            ->expects($this->any())
            ->method('loadDriverExtensions')
            ->will($this->returnValue(array()))
        ;

        $extensions = array($extension1, $extension2);
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');

        $factory = new DataSourceFactory($extensions);
        $datasource = $factory->createDataSource($driver);

        $factoryExtensions = $factory->getExtensions();
        $datasourceExtensions = $datasource->getExtensions();

        $this->assertEquals(count($factoryExtensions), count($extensions));
        $this->assertEquals(count($datasourceExtensions), count($extensions));
    }

    /**
     * Checks exception thrown when creating DataSource without driver.
     */
    public function testFactoryException1()
    {
        $this->setExpectedException('Exception');
        $factory = new DataSourceFactory();
        $datasource = $factory->createDataSource();
    }

    /**
     * Checks exception thrown when loading inproper extensions.
     */
    public function testFactoryException2()
    {
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $factory = new DataSourceFactory(array(new \stdClass()));
    }

    /**
     * Checks exception thrown when loading scalars in place of extensions.
     */
    public function testFactoryException3()
    {
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $factory = new DataSourceFactory(array('scalar'));
    }

	/**
     * Checks exception thrown when loading inproper extensions not in constructor.
     */
    public function testFactoryException4()
    {
        $this->setExpectedException('Exception');
        $factory = new DataSourceFactory();

        $factory->addExtension(new \stdClass());
    }

	/**
     * Checks exception thrown when loading scalar as extension not in constructor.
     */
    public function testFactoryException5()
    {
        $this->setExpectedException('Exception');
        $factory = new DataSourceFactory();

        $factory->addExtension('scalar');
    }

    /**
     * Checks exception thrown when creating DataSource with non unique name.
     */
    public function testFactoryCreateDataSourceException1()
    {
        $factory = new DataSourceFactory();
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');

        $datasource1 = $factory->createDataSource($driver, 'unique');
        $datasource1 = $factory->createDataSource($driver, 'nonunique');
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $datasource1 = $factory->createDataSource($driver, 'nonunique');
    }

    /**
     * Checks exception thrown when creating DataSource with wrong name.
     */
    public function testFactoryCreateDataSourceException2()
    {
        $factory = new DataSourceFactory();
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $datasource1 = $factory->createDataSource($driver, 'wrong-one');
    }

    /**
     * Checks exception thrown when creating DataSource with empty name.
     */
    public function testFactoryCreateDataSourceException3()
    {
        $factory = new DataSourceFactory();
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $datasource1 = $factory->createDataSource($driver, '');
    }

    /**
     * Checks adding DataSoucre to factory.
     */
    public function testAddDataSource()
    {
        $factory = new DataSourceFactory();
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $datasource2 = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));

        $datasource
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('name'))
        ;

        $datasource2
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('name'))
        ;

        $datasource
            ->expects($this->atLeastOnce())
            ->method('setFactory')
            ->with($factory)
        ;

        $factory->addDataSource($datasource);
        //Check if adding it twice won't cause exception.
        $factory->addDataSource($datasource);

        //Checking exception for adding different datasource with the same name.
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $factory->addDataSource($datasource2);
    }

    /**
     * Checks fetching parameters of all and others datasources.
     */
    public function testGetAllAndOtherParameters()
    {
        $factory = new DataSourceFactory();
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource1 = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $datasource2 = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));

        $params1 = array(
            'key1' => 'value1',
        );

        $params2 = array(
            'key2' => 'value2',
        );

        $result = array_merge($params1, $params2);

        $datasource1
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('name'))
        ;

        $datasource2
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('name2'))
        ;

        $datasource1
            ->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue($params1))
        ;

        $datasource2
            ->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue($params2))
        ;

        $factory->addDataSource($datasource1);
        $factory->addDataSource($datasource2);

        $this->assertEquals($factory->getOtherParameters($datasource1), $params2);
        $this->assertEquals($factory->getOtherParameters($datasource2), $params1);
        $this->assertEquals($factory->getAllParameters(), $result);
    }
}