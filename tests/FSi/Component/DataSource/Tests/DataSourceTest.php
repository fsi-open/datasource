<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests;

use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Tests\Fixtures\TestResult;

/**
 * Tests for DataSource.
 */
class DataSourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic creation of DataSource
     */
    public function testDataSourceCreate()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
    }

    /**
     * Checking assignation of names.
     */
    public function testDataSourceName()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver, 'name1');
        $this->assertEquals($datasource->getName(), 'name1');
        $datasource = new DataSource($driver, 'name2');
        $this->assertEquals($datasource->getName(), 'name2');
    }

    /**
     * Testing exception thrown when creating DataSource with non-driver.
     */
    public function testDataSourceCreateException1()
    {
        $this->setExpectedException('Exception');
        $datasource = new DataSource(new \stdClass());
    }

    /**
     * Testing exception thrown when creating DataSource with wrong name.
     */
    public function testDataSourceCreateException2()
    {
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver, 'wrong-name');
    }

    /**
     * Testing exception thrown when creating DataSource with empty name.
     */
    public function testDataSourceCreateException3()
    {
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver, '');
    }

    /**
     * Checks thrown exception when trying to load as extension something that isn't.
     */
    public function testDataSourceExtensionsLoadException()
    {
        $this->setExpectedException('Exception');
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $datasource->addExtension(new \stdClass());
    }

    /**
     * Checks loading of extensions.
     */
    public function testDataSourceExtensionsLoad()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $extension1 = $this->getMock('FSi\Component\DataSource\DataSourceExtensionInterface');
        $extension2 = $this->getMock('FSi\Component\DataSource\DataSourceExtensionInterface');

        $extension1
            ->expects($this->once())
            ->method('loadDriverExtensions')
            ->will($this->returnValue(array()))
        ;
        $extension2
            ->expects($this->once())
            ->method('loadDriverExtensions')
            ->will($this->returnValue(array()))
        ;

        $datasource->addExtension($extension1);
        $datasource->addExtension($extension2);

        $this->assertEquals(count($datasource->getExtensions()), 2);
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException1()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $datasource->addField('field', 'type');
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException2()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $datasource->addField('field', '', 'type');
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException3()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $datasource->addField('field');
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException4()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');

        $field = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');

        $field
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(null))
        ;

        $datasource->addField($field);
    }

    /**
     * Checks exception during adding two fields with the same name.
     */
    public function testWrongFieldAddException5()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');

        $field = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');
        $field
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('somename'))
        ;

        $datasource->addField($field);
        $datasource->addField($field);
    }

    /**
     * Checks creating, adding, getting and deleting fields.
     */
    public function testFieldManipulation()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);

        $field = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');

        $field
            ->expects($this->once())
            ->method('setName')
            ->with('name1')
        ;

        $field
            ->expects($this->once())
            ->method('setComparison')
            ->with('comp1')
        ;

        $field
            ->expects($this->once())
            ->method('setOptions')
        ;

        $field
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('name'))
        ;

        $field
            ->expects($this->exactly(2))
            ->method('removeDataSource')
        ;

        $driver
            ->expects($this->once())
            ->method('getFieldType')
            ->with('text')
            ->will($this->returnValue($field))
        ;

        $datasource->addField('name1', 'text', 'comp1');

        $this->assertEquals(count($datasource->getFields()), 1);
        $this->assertTrue($datasource->hasField('name1'));
        $this->assertFalse($datasource->hasField('wrong'));

        $datasource->clearFields();
        $this->assertEquals(count($datasource->getFields()), 0);

        $datasource->addField($field);
        $this->assertEquals(count($datasource->getFields()), 1);
        $this->assertTrue($datasource->hasField('name'));
        $this->assertFalse($datasource->hasField('name1'));
        $this->assertFalse($datasource->hasField('name2'));

        $this->assertEquals($field, $datasource->getField('name'));

        $this->assertTrue($datasource->removeField('name'));
        $this->assertEquals(count($datasource->getFields()), 0);
        $this->assertFalse($datasource->removeField('name'));

        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $datasource->getField('wrong');
    }

	/**
     * Checks behaviour when binding arrays and scalars.
     */
    public function testBindParametersException()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $datasource->bindParameters(array());
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $datasource->bindParameters('nonarray');
    }

    /**
     * Checks behaviour at bind and get data.
     */
    public function testBindAndGetResult()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $field = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');
        $testResult = new TestResult();

        $firstData = array(
            $datasource->getName() => array(
                DataSource::FIELDS => array('field' => 'value', 'other' => 'notimportant'),
            ),
        );
        $secondData = array(
            $datasource->getName() => array(
                DataSource::FIELDS => array('somefield' => 'somevalue'),
            ),
        );

        $field
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('field'))
        ;

        $field
            ->expects($this->exactly(2))
            ->method('bindParameter')
        ;

        $driver
            ->expects($this->once())
            ->method('getResult')
            ->with(array('field' => $field))
            ->will($this->returnValue($testResult))
        ;

        $datasource->addField($field);
        $datasource->bindParameters($firstData);
        $datasource->bindParameters($secondData);

        $result = $datasource->getResult();
    }

    /**
     * Tests exception when driver returns scalar.
     */
    public function testWrongResult1()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);

        $driver
            ->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue('scalar'))
        ;
        $this->setExpectedException('\FSi\Component\DataSource\Exception\DataSourceException');
        $datasource->getResult();
    }

    /**
     * Tests exception when driver return object, that doesn't implement Countable and IteratorAggregate interfaces.
     */
    public function testWrongResult2()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);

        $driver
            ->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue(new \stdClass()))
        ;
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $datasource->getResult();
    }

    /**
     * Checks if parameters for pagination are forwarded to driver.
     */
    public function testPagination()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);

        $max = 20;
        $first = 40;

        $datasource->setMaxResults($max);
        $datasource->setFirstResult($first);

        $this->assertEquals($datasource->getMaxResults(), $max);
        $this->assertEquals($datasource->getFirstResult(), $first);
    }

    /**
     * Checks calls of datasource extensions.
     */
    public function testExtensionHooks()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);

        $extension = $this->getMock('FSi\Component\DataSource\DataSourceExtensionInterface');
        $testResult = new TestResult();

        $extension
            ->expects($this->once())
            ->method('loadDriverExtensions')
            ->will($this->returnValue(array()))
        ;

        $extension
            ->expects($this->once())
            ->method('preBindParameters')
        ;

        $extension
            ->expects($this->once())
            ->method('postBindParameters')
        ;

        $extension
            ->expects($this->once())
            ->method('preGetResult')
        ;

        $extension
            ->expects($this->once())
            ->method('postGetResult')
        ;

        $extension
            ->expects($this->once())
            ->method('preBuildView')
        ;

        $extension
            ->expects($this->once())
            ->method('postBuildView')
        ;

        $extension
            ->expects($this->once())
            ->method('preGetParameters')
        ;

        $extension
            ->expects($this->once())
            ->method('postGetParameters')
        ;

        $driver
            ->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($testResult))
        ;

        $datasource->addExtension($extension);
        $datasource->bindParameters();
        $datasource->getResult();
        $datasource->getParameters();
        $datasource->createView();

        $result = $datasource->getResult();
    }

    /**
     * Checks preGetParameters and postGetParameters calls.
     */
    public function testGetParameters()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $field = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');
        $field2 = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');

        $datasource = new DataSource($driver);

        $field
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('key'))
        ;

        $field
            ->expects($this->atLeastOnce())
            ->method('getParameter')
            ->with(array())
        ;

        $field2
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('key2'))
        ;

        $field2
            ->expects($this->atLeastOnce())
            ->method('getParameter')
            ->with(array())
        ;

        $datasource->addField($field);
        $datasource->addField($field2);
        $datasource->getParameters();
    }

    /**
     * Checks view creation.
     */
    public function testViewCreation()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = new DataSource($driver);
        $view = $datasource->createView();
        $this->assertTrue($view instanceof DataSourceViewInterface);
    }

    /**
     * Checks factory assignation.
     */
    public function testFactoryAssignation()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $factory = $this->getMock('FSi\Component\DataSource\DataSourceFactoryInterface');

        $datasource = new DataSource($driver);
        $datasource->setFactory($factory);
        $this->assertEquals($datasource->getFactory(), $factory);
    }

    /**
     * Checks fetching parameters of all and others datasources.
     */
    public function testGetAllAndOthersParameters()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $factory = $this->getMock('FSi\Component\DataSource\DataSourceFactoryInterface');

        $datasource = new DataSource($driver);

        $factory
            ->expects($this->once())
            ->method('getOtherParameters')
            ->with($datasource)
        ;

        $factory
            ->expects($this->once())
            ->method('getAllParameters')
        ;

        $datasource->setFactory($factory);
        $datasource->getOtherParameters();
        $datasource->getAllParameters();
    }

    /**
     * Check if datasource loads extensions for driver that comes from its own extensions.
     */
    public function testDriverExtensionLoading()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $extension = $this->getMock('FSi\Component\DataSource\DataSourceExtensionInterface');
        $driverExtension = $this->getMock('FSi\Component\DataSource\Driver\DriverExtensionInterface');

        $extension
            ->expects($this->once())
            ->method('loadDriverExtensions')
            ->will($this->returnValue(array($driverExtension)))
        ;

        $driver
            ->expects($this->once())
            ->method('addExtension')
            ->with($driverExtension)
        ;

        $datasource = new DataSource($driver);
        $datasource->addExtension($extension);
    }
}