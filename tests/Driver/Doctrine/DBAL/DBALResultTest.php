<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALResult;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Paginator;
use RuntimeException;

class DBALResultTest extends TestBase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var
     */
    private $paginator;

    protected function setUp(): void
    {
        $this->connection = $this->getMemoryConnection();
        $this->loadTestData($this->connection);

        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_CATEGORY_NAME, 'c')
            ->setMaxResults(3);

        $this->paginator = new Paginator($qb);
    }

    public function testEmptyResult()
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_CATEGORY_NAME, 'c')
            ->where('0 = 1');

        $paginator = new Paginator($qb);
        $result = new DBALResult($paginator, '[id]');

        $this->assertCount(0, $result);
        $this->assertCount(0, $result->toArray());
    }

    public function testInvalidStringIndexField()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Index cannot be null');
        new DBALResult($this->paginator, '[invalid]');
    }

    public function testDuplicatedStringIndex()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Duplicate index "A"');
        new DBALResult($this->paginator, '[type]');
    }

    public function testStringIndexField()
    {
        $result = new DBALResult($this->paginator, '[id]');
        $this->assertCount(10, $result);
        $this->assertEquals([
            1 => ['id' => 1, 'type' => 'A', 'name' => 'name-1'],
            2 => ['id' => 2, 'type' => 'B', 'name' => 'name-2'],
            3 => ['id' => 3, 'type' => 'A', 'name' => 'name-3'],
        ], $result->toArray());
    }

    public function testDuplicatedCallbackIndex()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Duplicate index "C"');
        new DBALResult($this->paginator, function () {
            return 'C';
        });
    }

    public function testClosureresult()
    {
        $result = new DBALResult($this->paginator, function ($row) {
            return sprintf('A_%s', $row['id']);
        });

        $this->assertCount(10, $result);
        $this->assertEquals([
            'A_1' => ['id' => 1, 'type' => 'A', 'name' => 'name-1'],
            'A_2' => ['id' => 2, 'type' => 'B', 'name' => 'name-2'],
            'A_3' => ['id' => 3, 'type' => 'A', 'name' => 'name-3'],
        ], $result->toArray());
    }
}
