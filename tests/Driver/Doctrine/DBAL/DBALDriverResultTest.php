<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Paginator;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;

use function preg_replace;

class DBALDriverResultTest extends TestBase
{
    /**
     * @var Connection
     */
    private $connection;

    public function testTableResultCount(): void
    {
        $datasource = $this->getNewsDataSource();
        self::assertCount(100, $datasource->getResult());
    }

    public function testDoubleCallToGetResultReturnSameResultSet(): void
    {
        $datasource = $this->getNewsDataSource();
        self::assertSame($datasource->getResult(), $datasource->getResult());
    }

    public function testParametersFiltering(): void
    {
        $datasource = $this->getNewsDataSource()->addField('title', 'text', 'like');

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'title' => 'title-1',
                ],
            ],
        ];
        $datasource->bindParameters($parameters);

        // title-1, title-10-19, title-100
        self::assertCount(12, $datasource->getResult());
    }

    public function testPaginatedResult(): void
    {
        $datasource = $this->getNewsDataSource();
        $datasource->addField('title', 'text', 'like');
        $datasource->setMaxResults(10);

        $parameters = [
            $datasource->getName() => [
                PaginationExtension::PARAMETER_PAGE => 2,
                DataSourceInterface::PARAMETER_FIELDS => [
                    'title' => 'title-1',
                ],
            ],
        ];
        $datasource->bindParameters($parameters);

        $result = $datasource->getResult();

        // all result count
        self::assertCount(12, $result);
        // current page count
        self::assertCount(2, iterator_to_array($result));
    }

    public function testSortingField(): void
    {
        $datasource = $this->getNewsDataSource();
        $datasource->addField('title', 'text', 'like');
        $datasource->addField('content', 'text', 'like');
        $datasource->setMaxResults(10);

        $parameters = [
            $datasource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'content' => 'asc',
                    'title' => 'desc',
                ],
                DataSourceInterface::PARAMETER_FIELDS => [
                    'title' => 'title-1',
                ],
            ],
        ];
        $datasource->bindParameters($parameters);

        $result = $datasource->getResult();
        self::assertInstanceOf(Paginator::class, $result);
        self::assertCount(12, $result);
        self::assertCount(10, iterator_to_array($result));
        self::assertEquals('title-18', $result->getIterator()->current()['title']);
    }

    /**
     * Checks DataSource with DoctrineDriver using more sophisticated QueryBuilder.
     */
    public function testQueryWithJoins(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->connection->createQueryBuilder()
            ->select('n.id')
            ->addSelect('c.id category_id')
            ->from('news', 'n')
            ->join('n', 'category', 'c', 'n.category_id = c.id')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'n',
        ];

        $datasource = $dataSourceFactory
            ->createDataSource('doctrine-dbal', $driverOptions, 'name')
            ->addField('category', 'text', 'eq', ['field' => 'c.name'])
            ->setMaxResults(8);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'category' => 'name-10',
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();

        self::assertCount(37, $result);
        self::assertCount(8, iterator_to_array($result));
    }

    /**
     * Checks DataSource wtih DoctrineDriver using more sophisticated QueryBuilder.
     */
    public function testQueryWithAggregates(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->connection->createQueryBuilder()
            ->select('c.*')
            ->addSelect('COUNT(n.id) newscount')
            ->from(self::TABLE_CATEGORY_NAME, 'c')
            ->leftJoin('c', 'news', 'n', 'n.category_id = c.id')
            ->groupBy('c.id')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'c',
        ];

        $datasource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name');

        $datasource
            ->addField('category', 'text', 'like', [
                'field' => 'c.name',
            ])
            ->addField('newscount', 'number', 'gt', [
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having',
            ])
            ->setMaxResults(3)
        ;

        $datasource->bindParameters([
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => 3,
                ],
            ],
        ]);

        $result = $datasource->getResult();
        self::assertCount(6, $result);
        self::assertCount(3, iterator_to_array($result));

        self::assertRegExp(
            '/^SELECT c\.\*, COUNT\(n\.id\) newscount FROM category c '
            . 'LEFT JOIN news n ON n\.category_id = c\.id '
            . 'GROUP BY c\.id HAVING newscount > :newscount LIMIT 3( OFFSET 0)?$/',
            $this->testDoctrineExtension->getQueryBuilder()->getSQL()
        );

        $datasource->bindParameters([
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => ['newscount' => 0,],
            ],
        ]);

        $result = $datasource->getResult();
        self::assertCount(10, $result);
        self::assertCount(3, iterator_to_array($result));

        self::assertRegExp(
            '/^SELECT c\.\*, COUNT\(n\.id\) newscount FROM category c '
            . 'LEFT JOIN news n ON n\.category_id = c\.id '
            . 'GROUP BY c\.id HAVING newscount > :newscount LIMIT 3( OFFSET 0)?$/',
            $this->testDoctrineExtension->getQueryBuilder()->getSQL()
        );

        $datasource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name2');
        $datasource
            ->addField('category', 'text', 'like', [
                'field' => 'c.name',
            ])
            ->addField('newscount', 'number', 'between', [
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having'
            ])
            ->setMaxResults(2)
        ;

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => [0, 1],
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();
        self::assertCount(3, $result);
        self::assertCount(2, iterator_to_array($result));

        self::assertRegExp(
            '/^SELECT c\.\*, COUNT\(n\.id\) newscount FROM category c '
            . 'LEFT JOIN news n ON n\.category_id = c\.id '
            . 'GROUP BY c\.id HAVING newscount BETWEEN :newscount_from AND :newscount_to LIMIT 2( OFFSET 0)?$/',
            $this->testDoctrineExtension->getQueryBuilder()->getSQL()
        );
    }

    /**
     * Tests if 'having' value of 'clause' option works properly in 'entity' field
     */
    public function testHavingClauseInEntityField(): void
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->connection->createQueryBuilder()
            ->select('n')
            ->from(self::TABLE_NEWS_NAME, 'n')
            ->join('n', self::TABLE_CATEGORY_NAME, 'c', 'n.category_id = c.id')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'n'
        ];

        $datasource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name');
        $datasource
            ->addField('category', 'number', 'in', [
                'clause' => 'having'
            ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'category' => [2, 3],
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->getResult();

        self::assertEquals(
            preg_replace('/\s+/', ' ', 'SELECT n
            FROM news n
                INNER JOIN category c ON n.category_id = c.id
            HAVING n.category IN (:dcValue1, :dcValue2)'),
            $this->testDoctrineExtension->getQueryBuilder()->getSQL()
        );
    }

    protected function setUp(): void
    {
        $this->connection = $this->getMemoryConnection();
        $this->loadTestData($this->connection);
    }

    private function getNewsDataSource(): DataSourceInterface
    {
        return $this->getDataSourceFactory()->createDataSource('doctrine-dbal', ['table' => 'news'], 'name');
    }
}
