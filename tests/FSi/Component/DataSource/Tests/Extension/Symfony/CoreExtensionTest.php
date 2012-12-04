<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Extension\Symfony;

use FSi\Component\DataSource\Extension\Symfony\CoreExtension;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for Symfony Core Extension.
 */
class CoreExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('Symfony HttpFoundation needed!');
        }
    }

    /**
     * Checks if Request if converted correctly.
     */
    public function testBindParameters()
    {
        $extension = new CoreExtension();
        $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
        $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));
        $data1 = array('key1' => 'value1', 'key2' => 'value2');
        $data2 = $data1;

        $extension->preBindParameters($datasource, $data2);
        $this->assertEquals($data1, $data2);

        $request = new Request($data2);
        $extension->preBindParameters($datasource, $request);
        $this->assertTrue(is_array($request));
        $this->assertEquals($data1, $request);
    }
}