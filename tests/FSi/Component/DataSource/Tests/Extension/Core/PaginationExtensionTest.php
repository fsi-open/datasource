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

use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEvent;

/**
 * Tests for Pagination Extension.
 */
class PaginationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * First case of event (when page is not 1).
     */
    public function testPreGetParameters1()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $extension = new PaginationExtension();

        $datasource
            ->expects($this->any())
            ->method('getMaxResults')
            ->will($this->returnValue(20))
        ;

        $datasource
            ->expects($this->any())
            ->method('getFirstResult')
            ->will($this->returnValue(20))
        ;

        $datasource
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('datasource'))
        ;

        $data = array();
        $subscribers = $extension->loadSubscribers();
        $subscriber = array_shift($subscribers);
        $args = new DataSourceEvent\ParametersEventArgs($datasource, $data);
        $subscriber->preGetParameters($args);
        $pattern = array(
            'datasource' => array(
                DataSourceInterface::PAGE => 2
            )
        );
        $this->assertEquals($pattern, $args->getParameters());
    }

    /**
     * First case of event (when page is 1).
     */
    public function testPreGetParameters2()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $extension = new PaginationExtension();

        $datasource
            ->expects($this->any())
            ->method('getMaxResults')
            ->will($this->returnValue(0))
        ;

        $datasource
            ->expects($this->any())
            ->method('getFirstResult')
            ->will($this->returnValue(20))
        ;

        $datasource
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('datasource'))
        ;

        $data = array();
        $subscribers = $extension->loadSubscribers();
        $subscriber = array_shift($subscribers);
        $args = new DataSourceEvent\ParametersEventArgs($datasource, $data);
        $subscriber->preGetParameters($args);
        $this->assertEquals(array(), $args->getParameters());
    }

    /**
     * Checks setting options.
     */
    public function testPostBuildView()
    {
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSourceInterface', array(), array($driver));
        $datasourceView = $this->getMock('FSi\Component\DataSource\DataSourceViewInterface', array(), array($datasource));
        $extension = new PaginationExtension();

        $datasourceView
            ->expects($this->atLeastOnce())
            ->method('setAttribute')
        ;

        $subscribers = $extension->loadSubscribers();
        $subscriber = array_shift($subscribers);
        $subscriber->postBuildView(new DataSourceEvent\ViewEventArgs($datasource, $datasourceView));
    }
}