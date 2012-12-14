<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Extension\Core;

use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Event\FieldEvent;

/**
 * Tests for Ordering Extension.
 */
class OrderingExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Checks postBuildView event.
     */
    public function testPostBuildView()
    {
        $extension = new OrderingExtension();
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSourceInterface', array(), array($driver));
        $view = $this->getMock('FSi\Component\DataSource\DataSourceViewInterface', array(), array($datasource));

        $view
            ->expects($this->exactly(3))
            ->method('setAttribute')
        ;

        $datasource
            ->expects($this->any())
            ->method('getFields')
            ->will($this->returnValue(array()))
        ;

        $subscribers = $extension->loadSubscribers();
        $subscriber = array_shift($subscribers);
        $subscriber->postBuildView(new DataSourceEvent\ViewEventArgs($datasource, $view));
    }

    /**
     * Checks if passed values are correct.
     */
    public function testOrderingCount()
    {
        /**
         * Each position of input means: expected priority passed to view and then
         * arrays of fields values (if values have been set, given priority and expected priority passed to driver)
         */
        $input = array(
            array(
                4,
                array(true, 1, 5),
                array(true, 3, 6),
                array(false, 1, 2),
                array(false, 10, 3),
                array(true, null, 4),
                array(false, null, 1),
            ),
            array(
                5,
                array(true, 4, 7),
                array(true, 4, 6),
                array(false, null, 2),
                array(true, null, 4),
                array(false, 4, 3),
                array(false, null, 1),
                array(true, 3, 5),
            ),
            array(
                1,
                array(false, null, 5),
                array(false, null, 4),
                array(false, null, 3),
                array(false, null, 2),
                array(false, null, 1),
            )
        );

        foreach ($input as $case) {
            $fields = array();
            $expectedNextPriority = array_shift($case);
            $givenNextPriority = null;
            foreach ($case as $fieldData) {
                $field = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');
                $field
                    ->expects($this->any())
                    ->method('getOptions')
                    ->will($this->returnValue(array(
                        OrderingExtension::ORDERING_IS_GIVEN => $fieldData[0],
                        OrderingExtension::ORDERING_PRIORITY => $fieldData[1],
                    )))
                ;

                $field
                    ->expects($this->any())
                    ->method('hasOption')
                    ->will($this->returnValue(true))
                ;

                $field
                    ->expects($this->any())
                    ->method('getOption')
                    ->will($this->returnCallback(function () use ($fieldData) {
                        $args = func_get_args();
                        switch (array_shift($args)) {
                            case OrderingExtension::ORDERING_IS_GIVEN:
                                return $fieldData[0];

                            case OrderingExtension::ORDERING_PRIORITY:
                                return $fieldData[1];
                        }

                        throw new \Exception('Unsupported test case');
                    }))
                ;

                $expected = array(
                    OrderingExtension::ORDERING_IS_GIVEN => $fieldData[0],
                    OrderingExtension::ORDERING_PRIORITY => $fieldData[2],
                );

                $field
                    ->expects($this->once())
                    ->method('setOptions')
                    ->with($expected)
                ;

                $fields[] = $field;
            }

            $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
            $datasource = $this->getMock('FSi\Component\DataSource\DataSourceInterface', array(), array($driver));
            $view = $this->getMock('FSi\Component\DataSource\DataSourceViewInterface', array(), array($datasource));

            $datasource
                ->expects($this->any())
                ->method('getFields')
                ->will($this->returnValue($fields))
            ;

            $view
                ->expects($this->any())
                ->method('setAttribute')
                ->will($this->returnCallback(function () use (&$givenNextPriority) {
                    list($key, $value) = func_get_args();
                    if ($key == OrderingExtension::VIEW_NEXT_PRIORITY) {
                        $givenNextPriority = $value;
                    }
                }))
            ;

            $extension = new OrderingExtension();
            $subscribers = $extension->loadSubscribers();
            $subscriber = array_shift($subscribers);
            $subscriber->preGetResult(new DataSourceEvent\DataSourceEventArgs($datasource));
            $subscriber->postBuildView(new DataSourceEvent\ViewEventArgs($datasource, $view));
            $this->assertEquals($expectedNextPriority, $givenNextPriority);
        }
    }

    /**
     * Checks parameters manipulation.
     */
    public function testFieldParameters()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSourceInterface', array(), array($driver));

        $datasource
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('datasource'))
        ;

        $extension = new FieldExtension();

        $field = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');

        $field
            ->expects($this->any())
            ->method('getDataSource')
            ->will($this->returnValue($datasource))
        ;

        $field
            ->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue(array()))
        ;

        $field
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('field'))
        ;

        $field
            ->expects($this->once())
            ->method('setOptions')
            ->with(array(
                OrderingExtension::ORDERING => 'asc',
                OrderingExtension::ORDERING_PRIORITY => 1,
                OrderingExtension::ORDERING_IS_GIVEN => 1,
            ))
        ;

        $parameter = array(
            'datasource' => array(
                OrderingExtension::ORDERING => array(
                    'field' => array(
                        OrderingExtension::ORDERING => 'asc',
                        OrderingExtension::ORDERING_PRIORITY => 1,
                    ),
                ),
            ),
        );

        $extension->preBindParameter(new FieldEvent\ParameterEventArgs($field, $parameter));

        $parameter2 = array();
        $args = new FieldEvent\ParameterEventArgs($field, $parameter2);
        $extension->preGetParameter($args);
        $this->assertEquals($parameter, $args->getParameter());

        $enabled = null;
        $fieldView = $this->getMock('FSi\Component\DataSource\Field\FieldViewInterface', array(), array($field));
        $fieldView
            ->expects($this->any())
            ->method('setAttribute')
            ->will($this->returnCallback(function () use (&$enabled) {
                list($key, $value) = func_get_args();
                if ($key == OrderingExtension::VIEW_IS_ENABLED) {
                    $enabled = $value;
                }
            }))
        ;

        $extension->postBuildView(new FieldEvent\ViewEventArgs($field, $fieldView));
        $this->assertTrue((bool) $enabled);
    }
}