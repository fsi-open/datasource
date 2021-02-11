<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver;

use DateTime;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFactory;
use FSi\Component\DataSource\Driver\Doctrine\ORM;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Basic tests for Doctrine driver.
 */
class DriverFactoryManagerTest extends TestCase
{
    public function testBasicManagerOperations(): void
    {
        $doctrineDbalFactory = $this->createMock(DBALFactory::class);
        $doctrineDbalFactory->method('getDriverType')->willReturn('doctrine-dbal');

        $doctrineOrmFactory = $this->createMock(ORM\DoctrineFactory::class);
        $doctrineOrmFactory->method('getDriverType')->willReturn('doctrine-orm');

        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->method('getDriverType')->willReturn('collection');

        $manager = new DriverFactoryManager([$doctrineDbalFactory, $doctrineOrmFactory, $collectionFactory]);

        self::assertTrue($manager->hasFactory('doctrine-dbal'));
        self::assertTrue($manager->hasFactory('doctrine-orm'));
        self::assertTrue($manager->hasFactory('collection'));

        self::assertSame($doctrineDbalFactory, $manager->getFactory('doctrine-dbal'));
        self::assertSame($doctrineOrmFactory, $manager->getFactory('doctrine-orm'));
        self::assertSame($collectionFactory, $manager->getFactory('collection'));
    }

    public function testAddInvalidFactory(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DriverFactoryManager([new DateTime()]);
    }
}
