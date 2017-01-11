<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field;
use FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures\DBALDriverExtension;
use FSi\Component\DataSource\Tests\Fixtures\FieldExtension;

class DBALDriverTest extends TestBase
{
    /**
     * @var Connection
     */
    private $connection;

    protected function setUp()
    {
        if (!class_exists('Doctrine\DBAL\Connection')) {
            $this->markTestSkipped('Doctrine DBAL needed!');
        }

        $this->connection = $this->getMemoryConnection();
    }

    public function testCreation()
    {
        $qb = $this->connection->createQueryBuilder();

        new DBALDriver(array(), $this->connection, 'table');
        new DBALDriver(array(), $this->connection, $qb);
    }

    /**
     * Checks creation exception.
     */
    public function testCreationExceptionWhenExtensionIsInvalid()
    {
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        new DBALDriver(array(new \stdClass()), $this->connection, 'table');
    }

    /**
     * Checks creation exception.
     */
    public function testCreationExceptionWhenNoQueryBuilderAndTable()
    {
        $this->setExpectedException('FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException');
        new DBALDriver(array(), $this->connection, null);
    }

    /**
     * Checks exception when fields aren't proper instances.
     */
    public function testGetResultExceptionWhenFieldIsNotDBALField()
    {
        $driver = new DBALDriver(array(), $this->connection, 'table');
        $this->setExpectedException('FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException');

        $fields = array($this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface'));
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks basic getResult call.
     */
    public function testAllFieldsBuildQueryMethod()
    {
        $fields = array();

        for ($x = 0; $x < 6; $x++) {
            $field = $this->getMock('FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALAbstractField');

            $field
                ->expects($this->once())
                ->method('buildQuery')
            ;

            $fields[] = $field;
        }

        $driver = new DBALDriver(array(), $this->connection, 'table');
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks exception when trying to access the query builder not during getResult method.
     */
    public function testGetQueryExceptionWhenNotInsideGetResult()
    {
        $driver = new DBALDriver(array(), $this->connection, 'table');
        $this->setExpectedException('FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException');
        $driver->getQueryBuilder();
    }

    /**
     * Checks CoreExtension.
     */
    public function testCoreExtension()
    {
        $driver = new DBALDriver(array(new CoreExtension()), $this->connection, 'table');

        $this->assertTrue($driver->hasFieldType('text'));
        $this->assertTrue($driver->hasFieldType('number'));
        $this->assertTrue($driver->hasFieldType('date'));
        $this->assertTrue($driver->hasFieldType('time'));
        $this->assertTrue($driver->hasFieldType('datetime'));
        $this->assertTrue($driver->hasFieldType('boolean'));
        $this->assertFalse($driver->hasFieldType('wrong'));
        $this->assertFalse($driver->hasFieldType(null));

        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $driver->getFieldType('wrong');
    }

    /**
     * Checks extensions calls.
     */
    public function testExtensionsCalls()
    {
        $extension = new DBALDriverExtension();
        $driver = new DBALDriver(array(), $this->connection, 'table');
        $driver->addExtension($extension);

        $driver->getResult(array(), 0, 20);
        $this->assertEquals(array('preGetResult', 'postGetResult'), $extension->getCalls());
    }

    /**
     * Provides names of fields.
     *
     * @return array
     */
    public static function fieldNameProvider()
    {
        return array(
            array('text'),
            array('number'),
            array('date'),
            array('time'),
            array('datetime'),
            array('boolean'),
        );
    }

    /**
     * Checks all fields of CoreExtension.
     *
     * @dataProvider fieldNameProvider
     */
    public function testCoreFields($type)
    {
        $driver = new DBALDriver(array(new CoreExtension()), $this->connection, 'table');
        $this->assertTrue($driver->hasFieldType($type));
        $field = $driver->getFieldType($type);
        $this->assertTrue($field instanceof \FSi\Component\DataSource\Field\FieldTypeInterface);
        $this->assertTrue($field instanceof \FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFieldInterface);

        $this->assertTrue($field->getOptionsResolver()->isDefined('field'));

        $comparisons = $field->getAvailableComparisons();
        $this->assertTrue(count($comparisons) > 0);

        foreach ($comparisons as $cmp) {
            $field = $driver->getFieldType($type);
            $field->setName('name');
            $field->setComparison($cmp);
            $field->setOptions(array());
        }

        $this->assertEquals($field->getOption('field'), $field->getName());

        $this->setExpectedException('FSi\Component\DataSource\Exception\FieldException');
        $field = $driver->getFieldType($type);
        $field->setComparison('wrong');
    }

    /**
     * Checks fields extensions calls.
     */
    public function testFieldsExtensionsCalls()
    {
        $extension = new FieldExtension();
        $parameter = array();

        $fields = array(
            new Field\Text(),
            new Field\Number(),
            new Field\Date(),
            new Field\Time(),
            new Field\DateTime(),
        );

        foreach ($fields as $field) {
            $field->addExtension($extension);

            $field->bindParameter(array());
            $this->assertEquals(array('preBindParameter', 'postBindParameter'), $extension->getCalls());
            $extension->resetCalls();

            $field->getParameter($parameter);
            $this->assertEquals(array('postGetParameter'), $extension->getCalls());
            $extension->resetCalls();

            $field->createView(array());
            $this->assertEquals(array('postBuildView'), $extension->getCalls());
            $extension->resetCalls();
        }
    }
}
