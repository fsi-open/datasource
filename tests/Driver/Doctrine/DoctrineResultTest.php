<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine;

use FSi\Component\DataSource\Driver\Doctrine\DoctrineResult;

class DoctrineResultTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyPaginator()
    {
        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $paginator = $this->getMockBuilder('Doctrine\ORM\Tools\Pagination\Paginator')
            ->disableOriginalConstructor()
            ->getMock();

        $paginator->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue([]));

        $result = new DoctrineResult($registry, $paginator);
    }

    public function testResultWithNotObjectDataInRows()
    {
        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $paginator = $this->getMockBuilder('Doctrine\ORM\Tools\Pagination\Paginator')
            ->disableOriginalConstructor()
            ->getMock();

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
