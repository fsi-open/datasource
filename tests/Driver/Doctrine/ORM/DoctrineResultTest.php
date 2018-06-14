<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\ORM;

use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineResult;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineResultTest extends TestCase
{
    public function testEmptyPaginator()
    {
        /** @var MockObject|ManagerRegistry $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        /** @var MockObject|Paginator $paginator */
        $paginator = $this->createMock(Paginator::class);

        $paginator->expects($this->any())
            ->method('getIterator')
            ->willReturn([]);

        $result = new DoctrineResult($registry, $paginator);
        $this->assertCount(0, $result);
    }

    public function testResultWithNotObjectDataInRows()
    {
        /** @var MockObject|ManagerRegistry $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        /** @var MockObject|Paginator $paginator */
        $paginator = $this->createMock(Paginator::class);

        $paginator->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue([
                '0' => ['foo', 'bar'],
                '1' => ['foo1', 'bar1']
            ]));

        $result = new DoctrineResult($registry, $paginator);
        $this->assertSame($result['0'], ['foo', 'bar']);
        $this->assertSame($result['1'], ['foo1', 'bar1']);
    }
}
