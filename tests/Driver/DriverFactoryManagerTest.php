<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver;

use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Doctrine\DBAL;
use FSi\Component\DataSource\Driver\Doctrine\ORM;
use FSi\Component\DataSource\Driver\DriverFactoryManager;

/**
 * Basic tests for Doctrine driver.
 */
class DriverFactoryManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicManagerOperations()
    {
        $doctrineDbalFactory = $this->getMockBuilder(DBAL\DBalFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineDbalFactory->expects($this->any())
            ->method('getDriverType')
            ->will($this->returnValue('doctrine-dbal'));

        $doctrineOrmFactory = $this->getMockBuilder(ORM\DoctrineFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineOrmFactory->expects($this->any())
            ->method('getDriverType')
            ->will($this->returnValue('doctrine-orm'));

        $collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collectionFactory->expects($this->any())
            ->method('getDriverType')
            ->will($this->returnValue('collection'));


        $manager = new DriverFactoryManager([
            $doctrineDbalFactory,
            $doctrineOrmFactory,
            $collectionFactory
        ]);

        $this->assertTrue($manager->hasFactory('doctrine-dbal'));
        $this->assertTrue($manager->hasFactory('doctrine-orm'));
        $this->assertTrue($manager->hasFactory('collection'));

        $this->assertSame($doctrineDbalFactory, $manager->getFactory('doctrine-dbal'));
        $this->assertSame($doctrineOrmFactory, $manager->getFactory('doctrine-orm'));
        $this->assertSame($collectionFactory, $manager->getFactory('collection'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddInvalidFactory()
    {
        new DriverFactoryManager([new \DateTime()]);
    }
}
