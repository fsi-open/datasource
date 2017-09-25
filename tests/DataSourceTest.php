<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\DataSourceExtensionInterface;
use FSi\Component\DataSource\DataSourceFactoryInterface;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Tests\Fixtures\TestResult;
use FSi\Component\DataSource\Tests\Fixtures\DataSourceExtension;

/**
 * Tests for DataSource.
 */
class DataSourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic creation of DataSource.
     */
    public function testDataSourceCreate()
    {
        new DataSource($this->createDriverMock());
    }

    /**
     * Checking assignation of names.
     */
    public function testDataSourceName()
    {
        $driver = $this->createDriverMock();

        $datasource = new DataSource($driver, 'name1');
        $this->assertEquals($datasource->getName(), 'name1');

        $datasource = new DataSource($driver, 'name2');
        $this->assertEquals($datasource->getName(), 'name2');
    }

    /**
     * Testing exception thrown when creating DataSource with wrong name.
     */
    public function testDataSourceCreateException2()
    {
        $this->setExpectedException(DataSourceException::class);
        new DataSource($this->createDriverMock(), 'wrong-name');
    }

    /**
     * Testing exception thrown when creating DataSource with empty name.
     */
    public function testDataSourceCreateException3()
    {
        $this->setExpectedException(DataSourceException::class);
        new DataSource($this->createDriverMock(), '');
    }

    /**
     * Checks loading of extensions.
     */
    public function testDataSourceExtensionsLoad()
    {
        $datasource = new DataSource($this->createDriverMock());
        $extension1 = $this->createMock(DataSourceExtensionInterface::class);
        $extension2 = $this->createMock(DataSourceExtensionInterface::class);

        $extension1
            ->expects($this->once())
            ->method('loadDriverExtensions')
            ->will($this->returnValue([]))
        ;
        $extension2
            ->expects($this->once())
            ->method('loadDriverExtensions')
            ->will($this->returnValue([]))
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
        $datasource = new DataSource($this->createDriverMock());
        $this->setExpectedException(DataSourceException::class);
        $datasource->addField('field', 'type');
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException2()
    {
        $datasource = new DataSource($this->createDriverMock());
        $this->setExpectedException(DataSourceException::class);
        $datasource->addField('field', '', 'type');
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException3()
    {
        $datasource = new DataSource($this->createDriverMock());
        $this->setExpectedException(DataSourceException::class);
        $datasource->addField('field');
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException4()
    {
        $datasource = new DataSource($this->createDriverMock());
        $this->setExpectedException(DataSourceException::class);

        $field = $this->createMock(FieldTypeInterface::class);

        $field
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(null))
        ;

        $datasource->addField($field);
    }

    /**
     * Checks creating, adding, getting and deleting fields.
     */
    public function testFieldManipulation()
    {
        $driver = $this->createDriverMock();
        $datasource = new DataSource($driver);

        $field = $this->createMock(FieldTypeInterface::class);

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

        $this->setExpectedException(DataSourceException::class);
        $datasource->getField('wrong');
    }

    /**
     * Checks behaviour when binding arrays and scalars.
     */
    public function testBindParametersException()
    {
        $datasource = new DataSource($this->createDriverMock());
        $datasource->bindParameters([]);
        $this->setExpectedException(DataSourceException::class);
        $datasource->bindParameters('nonarray');
    }

    /**
     * Checks behaviour at bind and get data.
     */
    public function testBindAndGetResult()
    {
        $driver = $this->createDriverMock();
        $datasource = new DataSource($driver);
        $field = $this->createMock(FieldTypeInterface::class);
        $testResult = new TestResult();

        $firstData = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'field' => 'value',
                    'other' => 'notimportant'
                ],
            ],
        ];
        $secondData = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => ['somefield' => 'somevalue'],
            ],
        ];

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
            ->with(['field' => $field])
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
        $driver = $this->createDriverMock();
        $datasource = new DataSource($driver);

        $driver
            ->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue('scalar'))
        ;
        $this->setExpectedException(DataSourceException::class);
        $datasource->getResult();
    }

    /**
     * Tests exception when driver return object, that doesn't implement Countable and IteratorAggregate interfaces.
     */
    public function testWrongResult2()
    {
        $driver = $this->createDriverMock();
        $datasource = new DataSource($driver);

        $driver
            ->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue(new \stdClass()))
        ;
        $this->setExpectedException(DataSourceException::class);
        $datasource->getResult();
    }

    /**
     * Checks if parameters for pagination are forwarded to driver.
     */
    public function testPagination()
    {
        $datasource = new DataSource($this->createDriverMock());

        $max = 20;
        $first = 40;

        $datasource->setMaxResults($max);
        $datasource->setFirstResult($first);

        $this->assertEquals($datasource->getMaxResults(), $max);
        $this->assertEquals($datasource->getFirstResult(), $first);
    }

    /**
     * Checks preGetParameters and postGetParameters calls.
     */
    public function testGetParameters()
    {
        $field = $this->createMock(FieldTypeInterface::class);
        $field2 = $this->createMock(FieldTypeInterface::class);

        $datasource = new DataSource($this->createDriverMock());

        $field
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('key'))
        ;

        $field
            ->expects($this->atLeastOnce())
            ->method('getParameter')
            ->with([])
        ;

        $field2
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('key2'))
        ;

        $field2
            ->expects($this->atLeastOnce())
            ->method('getParameter')
            ->with([])
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
        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue(new ArrayCollection()))
        ;

        $datasource = new DataSource($driver);
        $view = $datasource->createView();
        $this->assertTrue($view instanceof DataSourceViewInterface);
    }

    /**
     * Checks factory assignation.
     */
    public function testFactoryAssignation()
    {
        $factory = $this->createMock(DataSourceFactoryInterface::class);

        $datasource = new DataSource($this->createDriverMock());
        $datasource->setFactory($factory);
        $this->assertEquals($datasource->getFactory(), $factory);
    }

    /**
     * Checks fetching parameters of all and others datasources.
     */
    public function testGetAllAndOthersParameters()
    {
        $factory = $this->createMock(DataSourceFactoryInterface::class);

        $datasource = new DataSource($this->createDriverMock());

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
        $driver = $this->createDriverMock();
        $extension = $this->createMock(DataSourceExtensionInterface::class);
        $driverExtension = $this->createMock(DriverExtensionInterface::class);

        $extension
            ->expects($this->once())
            ->method('loadDriverExtensions')
            ->will($this->returnValue([$driverExtension]))
        ;

        $driverExtension
            ->expects($this->once())
            ->method('getExtendedDriverTypes')
            ->will($this->returnValue(['fake']))
        ;

        $driver
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('fake'))
        ;

        $driver
            ->expects($this->once())
            ->method('addExtension')
            ->with($driverExtension)
        ;

        $datasource = new DataSource($driver);
        $datasource->addExtension($extension);
    }

    /**
     * Checks extensions calls.
     */
    public function testExtensionsCalls()
    {
        $driver = $this->createDriverMock();
        $extension = new DataSourceExtension();
        $datasource = new DataSource($driver);
        $datasource->addExtension($extension);

        $testResult = new TestResult();
        $driver
            ->expects($this->any())
            ->method('getResult')
            ->will($this->returnValue($testResult))
        ;

        $datasource->bindParameters([]);
        $this->assertEquals(['preBindParameters', 'postBindParameters'], $extension->getCalls());
        $extension->resetCalls();

        $datasource->getResult();
        $this->assertEquals(['preGetResult', 'postGetResult'], $extension->getCalls());
        $extension->resetCalls();

        $datasource->getParameters();
        $this->assertEquals(['preGetParameters', 'postGetParameters'], $extension->getCalls());
        $extension->resetCalls();

        $datasource->createView();
        $this->assertEquals(['preBuildView', 'postBuildView'], $extension->getCalls());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
