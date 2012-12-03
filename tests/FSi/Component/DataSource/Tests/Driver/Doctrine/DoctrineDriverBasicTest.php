<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine;

use FSi\Component\DataSource\Driver\Doctrine\DoctrineDriver;
use FSi\Component\DataSource\Driver\Doctrine\Extension\Core\CoreExtension;

/**
 * Basic tests for Doctrine driver.
 */
class DoctrineDriverBasicTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        if (!class_exists('Doctrine\ORM\EntityManager')) {
            $this->markTestSkipped('Doctrine needed!');
            return;
        }
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
            array('entity'),
            array('date'),
            array('time'),
            array('datetime'),
        );
    }

    /**
     * Returns mock of EntityManager.
     *
     * @return object
     */
    private function getEntityManagerMock()
    {
        return $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * Checks creation.
     */
    public function testCreation()
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getMock('Doctrine\ORM\QueryBuilder', array(), array($em));
        new DoctrineDriver(array(), $em, 'entity');
        new DoctrineDriver(array(), $em, $qb);
    }

    /**
     * Checks creation exception.
     */
    public function testCreationException1()
    {
        $this->setExpectedException('Exception');
        new DoctrineDriver(array(), new \stdClass(), 'entity');
    }

    /**
     * Checks creation exception.
     */
    public function testCreationException2()
    {
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $em = $this->getEntityManagerMock();
        new DoctrineDriver('scalar', $em, 'entity');
    }

    /**
     * Checks creation exception.
     */
    public function testCreationException3()
    {
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $em = $this->getEntityManagerMock();
        new DoctrineDriver(array(new \stdClass()), $em, 'entity');
    }

    /**
     * Checks creation exception.
     */
    public function testCreationException4()
    {
        $this->setExpectedException('FSi\Component\DataSource\Driver\Doctrine\Exception\DoctrineDriverException');
        $em = $this->getEntityManagerMock();
        new DoctrineDriver(array(), $em, null);
    }

    /**
     * Checks basic getResult and events calls.
     */
    public function testGetResult()
    {
        $fields = array();

        for ($x = 0; $x < 6; $x++) {
            $field = $this->getMock('FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField');

            $field
                ->expects($this->atLeastOnce())
                ->method('buildQuery')
            ;

            $field
                ->expects($this->atLeastOnce())
                ->method('setOrder')
            ;

            $fields[] = $field;
        }

        $em = $this->getEntityManagerMock();
        $qb = $this->getMock('Doctrine\ORM\QueryBuilder', array(), array($em));

        $em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb))
        ;

        $qb
            ->expects($this->any())
            ->method('select')
            ->will($this->returnValue($qb))
        ;

        $extension = $this->getMock('FSi\Component\DataSource\Driver\DriverAbstractExtension');

        $extension
            ->expects($this->atLeastOnce())
            ->method('preGetResult')
        ;

        $extension
            ->expects($this->atLeastOnce())
            ->method('postGetResult')
        ;

        $driver = new DoctrineDriver(array($extension), $em, 'entity');
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks exception when fields aren't proper instances.
     */
    public function testGetResultException1()
    {
        $fields = array($this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface'));

        $em = $this->getEntityManagerMock();
        $qb = $this->getMock('Doctrine\ORM\QueryBuilder', array(), array($em));

        $em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb))
        ;

        $qb
            ->expects($this->any())
            ->method('select')
            ->will($this->returnValue($qb))
        ;

        $driver = new DoctrineDriver(array(), $em, 'entity');
        $this->setExpectedException('FSi\Component\DataSource\Driver\Doctrine\Exception\DoctrineDriverException');
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks exception when trying to access the query builder not during getResult method.
     */
    public function testGetQueryException()
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getMock('Doctrine\ORM\QueryBuilder', array(), array($em));

        $em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb))
        ;

        $qb
            ->expects($this->any())
            ->method('select')
            ->will($this->returnValue($qb))
        ;

        $driver = new DoctrineDriver(array(), $em, 'entity');
        $this->setExpectedException('FSi\Component\DataSource\Driver\Doctrine\Exception\DoctrineDriverException');
        $driver->getQueryBuilder();
    }

    /**
     * Checks if query is accessible during events.
     */
    public function testGetQuery()
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getMock('Doctrine\ORM\QueryBuilder', array(), array($em));

        $em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb))
        ;

        $qb
            ->expects($this->any())
            ->method('select')
            ->will($this->returnValue($qb))
        ;

        $extension = $this->getMock('FSi\Component\DataSource\Driver\DriverAbstractExtension');
        $driver = new DoctrineDriver(array($extension), $em, 'entity');

        $extension
            ->expects($this->atLeastOnce())
            ->method('preGetResult')
            ->will($this->returnCallback(function () use (&$driver) {
                $driver->getQueryBuilder();
            }))
        ;

        $extension
            ->expects($this->atLeastOnce())
            ->method('postGetResult')
            ->will($this->returnCallback(function () use (&$driver) {
                $driver->getQueryBuilder();
            }))
        ;

        $driver->getResult(array(), 0, 20);
    }

    /**
     * Checks CoreExtension.
     */
    public function testCoreExtension()
    {
        $em = $this->getEntityManagerMock();
        $driver = new DoctrineDriver(array(new CoreExtension()), $em, 'entity');

        $this->assertTrue($driver->hasFieldType('text'));
        $this->assertTrue($driver->hasFieldType('number'));
        $this->assertTrue($driver->hasFieldType('entity'));
        $this->assertTrue($driver->hasFieldType('date'));
        $this->assertTrue($driver->hasFieldType('time'));
        $this->assertTrue($driver->hasFieldType('datetime'));
        $this->assertFalse($driver->hasFieldType('wrong'));

        $driver->getFieldType('text');
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $driver->getFieldType('wrong');
    }

    /**
     * Checks all fields of CoreExtension.
     *
     * @dataProvider fieldNameProvider
     */
    public function testCoreFields($type)
    {
        $em = $this->getEntityManagerMock();
        $driver = new DoctrineDriver(array(new CoreExtension()), $em, 'entity');
        $this->assertTrue($driver->hasFieldType($type));
        $field = $driver->getFieldType($type);
        $this->assertTrue($field instanceof \FSi\Component\DataSource\Field\FieldTypeInterface);
        $this->assertTrue($field instanceof \FSi\Component\DataSource\Driver\Doctrine\DoctrineFieldInterface);

        $comparisons = $field->getAvailableComparisons();
        $this->assertTrue(count($comparisons) > 0);

        foreach ($comparisons as $cmp) {
            $field = $driver->getFieldType($type);
            $field->setName('name');
            $field->setComparison($cmp);
            $field->setOptions(array());
        }

        $this->setExpectedException('FSi\Component\DataSource\Exception\FieldException');
        $field = $driver->getFieldType($type);
        $field->setComparison('wrong');
    }
}