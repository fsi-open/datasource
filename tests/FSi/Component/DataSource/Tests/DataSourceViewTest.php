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

use FSi\Component\DataSource\DataSourceView;

/**
 * Tests for DataSourceView.
 */
class DataSourceViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks creation of view.
     */
    public function testViewCreate()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $view = new DataSourceView($datasource);
    }

    /**
     * Checks if view properly proxy some requests to datasource.
     */
    public function testGetParameters()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $view = new DataSourceView($datasource);

        $datasource
            ->expects($this->once())
            ->method('getParameters')
        ;

        $datasource
            ->expects($this->once())
            ->method('getAllParameters')
        ;

        $datasource
            ->expects($this->once())
            ->method('getOtherParameters')
        ;

        $view->getParameters();
        $view->getOtherParameters();
        $view->getAllParameters();
    }

    /**
     * Checks if view count page properly.
     */
    public function testPagination()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $view = new DataSourceView($datasource);

        $datasource
            ->expects($this->at(1))
            ->method('getFirstResult')
            ->will($this->returnValue(0))
        ;

        $datasource
            ->expects($this->at(3))
            ->method('getFirstResult')
            ->will($this->returnValue(20))
        ;

        $datasource
            ->expects($this->at(5))
            ->method('getFirstResult')
            ->will($this->returnValue(19))
        ;

        $datasource
            ->expects($this->any())
            ->method('getMaxResults')
            ->will($this->returnValue(20))
        ;

        $this->assertEquals($view->getCurrentPage(), 1);
        $this->assertEquals($view->getCurrentPage(), 2);
        $this->assertEquals($view->getCurrentPage(), 1);

        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $view = new DataSourceView($datasource);

        $datasource
            ->expects($this->any())
            ->method('getMaxResults')
            ->will($this->returnValue(0))
        ;

        $datasource
            ->expects($this->any())
            ->method('getFirstResult')
            ->will($this->returnValue(100))
        ;

        $this->assertEquals($view->getCurrentPage(), 1);
        $this->assertEquals($view->getCurrentPage(), 1);
    }

    /**
     * Checks if view count amount of pages correctly.
     */
    public function testPageCount()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $view = new DataSourceView($datasource);
        $result = $this->getMock('Countable');

        $result
            ->expects($this->any())
            ->method('count')
            ->will($this->returnValue(121))
        ;

        $datasource
            ->expects($this->any())
            ->method('getResult')
            ->will($this->returnValue($result))
        ;

        $datasource
            ->expects($this->at(0))
            ->method('getMaxResults')
            ->will($this->returnValue(0))
        ;

        $datasource
            ->expects($this->at(1))
            ->method('getMaxResults')
            ->will($this->returnValue(10))
        ;

        $datasource
            ->expects($this->at(3))
            ->method('getMaxResults')
            ->will($this->returnValue(33))
        ;

        $datasource
            ->expects($this->at(5))
            ->method('getMaxResults')
            ->will($this->returnValue(11))
        ;

        $this->assertEquals($view->countPages(), 1);
        $this->assertEquals($view->countPages(), 13);
        $this->assertEquals($view->countPages(), 4);
        $this->assertEquals($view->countPages(), 11);
    }

    /**
     * Checks the correctness of options related methods.
     */
    public function testOptionsManipulation()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $view = new DataSourceView($datasource);

        $this->assertFalse($view->hasOption('option1'));
        $view->setOption('option1', 'value1');
        $this->assertTrue($view->hasOption('option1'));
        $this->assertEquals($view->getOption('option1'), 'value1');
        $view->removeOption('option1');
        $this->assertFalse($view->hasOption('option1'));

        $view->setOption('option2', '');
        $this->assertTrue($view->hasOption('option2'));

        $view->setOption('option2', null);
        $this->assertFalse($view->hasOption('option2'));

        $view->setOption('option3', 'value3');
        $view->setOption('option4', 'value4');

        $this->assertEquals($view->getOptions(), array('option3' => 'value3', 'option4' => 'value4'));

        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceViewException');
        $view->getOption('option2');
    }

    /**
     * Checks the correctness of field related methods.
     */
    public function testFieldsManipulation()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $field1 = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');
        $fieldView1 = $this->getMock('FSi\Component\DataSource\Field\FieldViewInterface', array(), array($field1));
        $field2 = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');
        $fieldView2 = $this->getMock('FSi\Component\DataSource\Field\FieldViewInterface', array(), array($field2));
        $view = new DataSourceView($datasource);

        $field1
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('name1'))
        ;

        $fieldView1
            ->expects($this->any())
            ->method('getField')
            ->will($this->returnValue($field1))
        ;

        $field2
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('name2'))
        ;

        $fieldView2
            ->expects($this->any())
            ->method('getField')
            ->will($this->returnValue($field2))
        ;

        $view->addField($fieldView1);
        $view->addField($fieldView2);

        $this->assertEquals(count($view->getFields()), 2);
        $this->assertTrue($view->hasField('name1'));
        $this->assertTrue($view->hasField('name2'));
        $this->assertFalse($view->hasField('wrong'));

        //Should be no exception throwed.
        $view->getField('name1');
        $view->getField('name2');

        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceViewException');
        $view->addField($fieldView1);
    }

    /**
     * Checks exception throwed when adding something different than field view.
     */
    public function testAddFieldException1()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $view = new DataSourceView($datasource);

        $this->setExpectedException('Exception');
        $view->addField(new \stdClass());
    }

    /**
     * Checks exception throwed when adding another field with the same name.
     */
    public function testAddFieldException2()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $field = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');
        $fieldView = $this->getMock('FSi\Component\DataSource\Field\FieldViewInterface', array(), array($field));
        $view = new DataSourceView($datasource);

        $field
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('name'))
        ;

        $fieldView
            ->expects($this->any())
            ->method('getField')
            ->will($this->returnValue($field))
        ;

        $view->addField($fieldView);

        $view->getField('name');
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceViewException');
        $view->getField('wrong');
    }
}