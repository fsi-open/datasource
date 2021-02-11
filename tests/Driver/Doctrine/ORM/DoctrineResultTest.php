<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\ORM;

use ArrayIterator;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DoctrineResultTest extends TestCase
{
    public function testEmptyPaginator(): void
    {
        /** @var MockObject&ManagerRegistry $registry */
        $registry = $this->createMock(ManagerRegistry::class);

        /** @var MockObject&Paginator $paginator */
        $paginator = $this->createMock(Paginator::class);
        $paginator->method('getIterator')->willReturn(new ArrayIterator([]));

        $result = new DoctrineResult($registry, $paginator);
        self::assertCount(0, $result);
    }

    public function testResultWithNotObjectDataInRows(): void
    {
        /** @var MockObject&ManagerRegistry $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        /** @var MockObject|Paginator $paginator */
        $paginator = $this->createMock(Paginator::class);

        $paginator->method('getIterator')
            ->willReturn(
                new ArrayIterator(
                    [
                        '0' => ['foo', 'bar'],
                        '1' => ['foo1', 'bar1']
                    ]
                )
            );

        $result = new DoctrineResult($registry, $paginator);
        self::assertSame($result['0'], ['foo', 'bar']);
        self::assertSame($result['1'], ['foo1', 'bar1']);
    }
}
