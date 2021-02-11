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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class DataSourceTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testDataSourceCreate(): void
    {
        new DataSource($this->createDriverMock());
    }

    /**
     * Checking assignation of names.
     */
    public function testDataSourceName(): void
    {
        $driver = $this->createDriverMock();

        $datasource = new DataSource($driver, 'name1');
        self::assertEquals('name1', $datasource->getName());

        $datasource = new DataSource($driver, 'name2');
        self::assertEquals('name2', $datasource->getName());
    }

    /**
     * Testing exception thrown when creating DataSource with wrong name.
     */
    public function testDataSourceCreateException2(): void
    {
        $this->expectException(DataSourceException::class);
        new DataSource($this->createDriverMock(), 'wrong-name');
    }

    /**
     * Testing exception thrown when creating DataSource with empty name.
     */
    public function testDataSourceCreateException3(): void
    {
        $this->expectException(DataSourceException::class);
        new DataSource($this->createDriverMock(), '');
    }

    /**
     * Checks loading of extensions.
     */
    public function testDataSourceExtensionsLoad(): void
    {
        $datasource = new DataSource($this->createDriverMock());
        $extension1 = $this->createMock(DataSourceExtensionInterface::class);
        $extension2 = $this->createMock(DataSourceExtensionInterface::class);

        $extension1->expects(self::once())->method('loadDriverExtensions')->willReturn([]);
        $extension2->expects(self::once())->method('loadDriverExtensions')->willReturn([]);

        $datasource->addExtension($extension1);
        $datasource->addExtension($extension2);

        self::assertCount(2, $datasource->getExtensions());
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException1(): void
    {
        $datasource = new DataSource($this->createDriverMock());
        $this->expectException(DataSourceException::class);
        $datasource->addField('field', 'type');
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException2(): void
    {
        $datasource = new DataSource($this->createDriverMock());
        $this->expectException(DataSourceException::class);
        $datasource->addField('field', '', 'type');
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException3(): void
    {
        $datasource = new DataSource($this->createDriverMock());
        $this->expectException(DataSourceException::class);
        $datasource->addField('field');
    }

    /**
     * Checks exception during field adding.
     */
    public function testWrongFieldAddException4(): void
    {
        $datasource = new DataSource($this->createDriverMock());
        $this->expectException(DataSourceException::class);

        $field = $this->createMock(FieldTypeInterface::class);
        $field->expects(self::once())->method('getName')->willReturn(null);

        $datasource->addField($field);
    }

    /**
     * Checks creating, adding, getting and deleting fields.
     */
    public function testFieldManipulation(): void
    {
        $driver = $this->createDriverMock();
        $datasource = new DataSource($driver);

        $field = $this->createMock(FieldTypeInterface::class);
        $field->expects(self::once())->method('setName')->with('name1');
        $field->expects(self::once())->method('setComparison')->with('comp1');
        $field->expects(self::once())->method('setOptions');
        $field->expects(self::once())->method('getName')->willReturn('name');

        $driver->expects(self::once())->method('getFieldType')->with('text')->willReturn($field);

        $datasource->addField('name1', 'text', 'comp1');

        self::assertCount(1, $datasource->getFields());
        self::assertTrue($datasource->hasField('name1'));
        self::assertFalse($datasource->hasField('wrong'));

        $datasource->clearFields();
        self::assertCount(0, $datasource->getFields());

        $datasource->addField($field);
        self::assertCount(1, $datasource->getFields());
        self::assertTrue($datasource->hasField('name'));
        self::assertFalse($datasource->hasField('name1'));
        self::assertFalse($datasource->hasField('name2'));

        self::assertEquals($field, $datasource->getField('name'));

        self::assertTrue($datasource->removeField('name'));
        self::assertCount(0, $datasource->getFields());
        self::assertFalse($datasource->removeField('name'));

        $this->expectException(DataSourceException::class);
        $datasource->getField('wrong');
    }

    /**
     * Checks behaviour when binding arrays and scalars.
     */
    public function testBindParametersException(): void
    {
        $datasource = new DataSource($this->createDriverMock());
        $datasource->bindParameters([]);
        $this->expectException(DataSourceException::class);
        $datasource->bindParameters('nonarray');
    }

    /**
     * Checks behaviour at bind and get data.
     */
    public function testBindAndGetResult(): void
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

        $field->method('getName')->willReturn('field');
        $field->expects(self::exactly(2))->method('bindParameter');

        $driver->expects(self::once())->method('getResult')->with(['field' => $field])->willReturn($testResult);

        $datasource->addField($field);
        $datasource->bindParameters($firstData);
        $datasource->bindParameters($secondData);

        $datasource->getResult();
    }

    /**
     * Tests exception when driver returns scalar.
     */
    public function testWrongResult1(): void
    {
        $driver = $this->createDriverMock();
        $datasource = new DataSource($driver);

        $driver->expects(self::once())->method('getResult')->willReturn('scalar');

        $this->expectException(DataSourceException::class);
        $datasource->getResult();
    }

    /**
     * Tests exception when driver return object, that doesn't implement Countable and IteratorAggregate interfaces.
     */
    public function testWrongResult2(): void
    {
        $driver = $this->createDriverMock();
        $datasource = new DataSource($driver);

        $driver->expects(self::once())->method('getResult')->willReturn(new stdClass());

        $this->expectException(DataSourceException::class);
        $datasource->getResult();
    }

    /**
     * Checks if parameters for pagination are forwarded to driver.
     */
    public function testPagination(): void
    {
        $datasource = new DataSource($this->createDriverMock());

        $max = 20;
        $first = 40;

        $datasource->setMaxResults($max);
        $datasource->setFirstResult($first);

        self::assertEquals($max, $datasource->getMaxResults());
        self::assertEquals($first, $datasource->getFirstResult());
    }

    /**
     * Checks preGetParameters and postGetParameters calls.
     */
    public function testGetParameters(): void
    {
        $field = $this->createMock(FieldTypeInterface::class);
        $field2 = $this->createMock(FieldTypeInterface::class);

        $datasource = new DataSource($this->createDriverMock());

        $field->expects(self::atLeastOnce())->method('getName')->willReturn('key');
        $field->expects(self::atLeastOnce())->method('getParameter')->with([]);

        $field2->expects(self::atLeastOnce())->method('getName')->willReturn('key2');
        $field2->expects(self::atLeastOnce())->method('getParameter')->with([]);

        $datasource->addField($field);
        $datasource->addField($field2);
        $datasource->getParameters();
    }

    /**
     * Checks view creation.
     */
    public function testViewCreation(): void
    {
        $driver = $this->createDriverMock();
        $driver->expects(self::once())->method('getResult')->willReturn(new ArrayCollection());

        $datasource = new DataSource($driver);
        $view = $datasource->createView();
        self::assertInstanceOf(DataSourceViewInterface::class, $view);
    }

    /**
     * Checks factory assignation.
     */
    public function testFactoryAssignation(): void
    {
        $factory = $this->createMock(DataSourceFactoryInterface::class);

        $datasource = new DataSource($this->createDriverMock());
        $datasource->setFactory($factory);

        self::assertEquals($factory, $datasource->getFactory());
    }

    /**
     * Checks fetching parameters of all and others datasources.
     */
    public function testGetAllAndOthersParameters(): void
    {
        $factory = $this->createMock(DataSourceFactoryInterface::class);

        $datasource = new DataSource($this->createDriverMock());

        $factory->expects(self::once())->method('getOtherParameters')->with($datasource);
        $factory->expects(self::once())->method('getAllParameters');

        $datasource->setFactory($factory);
        $datasource->getOtherParameters();
        $datasource->getAllParameters();
    }

    /**
     * Check if datasource loads extensions for driver that comes from its own extensions.
     */
    public function testDriverExtensionLoading(): void
    {
        $driver = $this->createDriverMock();
        $extension = $this->createMock(DataSourceExtensionInterface::class);
        $driverExtension = $this->createMock(DriverExtensionInterface::class);

        $extension->expects(self::once())->method('loadDriverExtensions')->willReturn([$driverExtension]);
        $driverExtension->expects(self::once())->method('getExtendedDriverTypes')->willReturn(['fake']);

        $driver->expects(self::once())->method('getType')->willReturn('fake');
        $driver->expects(self::once())->method('addExtension')->with($driverExtension);

        $datasource = new DataSource($driver);
        $datasource->addExtension($extension);
    }

    /**
     * Checks extensions calls.
     */
    public function testExtensionsCalls(): void
    {
        $driver = $this->createDriverMock();
        $extension = new DataSourceExtension();
        $datasource = new DataSource($driver);
        $datasource->addExtension($extension);

        $testResult = new TestResult();
        $driver->method('getResult')->willReturn($testResult);

        $datasource->bindParameters([]);
        self::assertEquals(['preBindParameters', 'postBindParameters'], $extension->getCalls());
        $extension->resetCalls();

        $datasource->getResult();
        self::assertEquals(['preGetResult', 'postGetResult'], $extension->getCalls());
        $extension->resetCalls();

        $datasource->getParameters();
        self::assertEquals(['preGetParameters', 'postGetParameters'], $extension->getCalls());
        $extension->resetCalls();

        $datasource->createView();
        self::assertEquals(['preBuildView', 'postBuildView'], $extension->getCalls());
    }

    /**
     * @return DriverInterface&MockObject
     */
    private function createDriverMock(): DriverInterface
    {
        return $this->createMock(DriverInterface::class);
    }
}
