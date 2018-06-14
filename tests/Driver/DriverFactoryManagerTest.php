<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver;

use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFactory;
use FSi\Component\DataSource\Driver\Doctrine\ORM;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use PHPUnit\Framework\TestCase;

/**
 * Basic tests for Doctrine driver.
 */
class DriverFactoryManagerTest extends TestCase
{
    public function testBasicManagerOperations()
    {
        $doctrineDbalFactory = $this->createMock(DBALFactory::class);
        $doctrineDbalFactory->expects($this->any())
            ->method('getDriverType')
            ->willReturn('doctrine-dbal');

        $doctrineOrmFactory = $this->createMock(ORM\DoctrineFactory::class);
        $doctrineOrmFactory->expects($this->any())
            ->method('getDriverType')
            ->willReturn('doctrine-orm');

        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->expects($this->any())
            ->method('getDriverType')
            ->willReturn('collection');

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

    public function testAddInvalidFactory()
    {
        $this->expectException(\InvalidArgumentException::class);

        new DriverFactoryManager([new \DateTime()]);
    }
}
