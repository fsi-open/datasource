<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Extension\Core;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Extension\Symfony;

class DBALResultTestBase extends TestBase
{
    /**
     * @var Connection
     */
    private $connection;

    protected function setUp()
    {
        if (!class_exists('Doctrine\DBAL\Connection')) {
            $this->markTestSkipped('Doctrine DBAL needed!');
        }

        $this->connection = $this->getMemoryConnection();
        $this->loadTestData($this->connection);
    }

    public function testTableResultCount()
    {
        $datasource = $this->getNewsDataSource();
        $this->assertEquals(100, count($datasource->getResult()));
    }

    public function testDoubleCallToGetResultReturnSameResultSet()
    {
        $datasource = $this->getNewsDataSource();
        $this->assertSame($datasource->getResult(), $datasource->getResult());
    }

    public function testParametersFiltering()
    {
        $datasource = $this->getNewsDataSource();
        $datasource->addField('title', 'text', 'like');

        $parameters = array(
            $datasource->getName() => array(
                DataSourceInterface::PARAMETER_FIELDS => array(
                    'title' => 'title-1',
                ),
            ),
        );
        $datasource->bindParameters($parameters);

        //title-1, title-10-19, title-100
        $this->assertEquals(12, count($datasource->getResult()));
    }

    public function testPaginatedResult()
    {
        $datasource = $this->getNewsDataSource();
        $datasource->addField('title', 'text', 'like');
        $datasource->setMaxResults(10);

        $parameters = array(
            $datasource->getName() => array(
                PaginationExtension::PARAMETER_PAGE => 2,
                DataSourceInterface::PARAMETER_FIELDS => array(
                    'title' => 'title-1',
                ),
            ),
        );
        $datasource->bindParameters($parameters);

        $result = $datasource->getResult();

        //all result count
        $this->assertEquals(12, count($result));
        //current page count
        $this->assertEquals(2, count(iterator_to_array($result)));
    }

    public function testSortingField()
    {
        $datasource = $this->getNewsDataSource();
        $datasource->addField('title', 'text', 'like');
        $datasource->addField('content', 'text', 'like');
        $datasource->setMaxResults(10);

        $parameters = array(
            $datasource->getName() => array(
                OrderingExtension::PARAMETER_SORT => array(
                    'content' => 'asc',
                    'title' => 'desc',
                ),
                DataSourceInterface::PARAMETER_FIELDS => array(
                    'title' => 'title-1',
                ),
            ),
        );
        $datasource->bindParameters($parameters);

        $result = $datasource->getResult();
        $this->assertCount(12, $result);
        $this->assertCount(10, iterator_to_array($result));

        foreach ($result as $row) {
            $this->assertEquals('title-18', $row['title']);
            break;
        }
    }

    /**
     * Checks DataSource wtih DoctrineDriver using more sophisticated QueryBuilder.
     */
    public function testQueryWithJoins()
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('news', 'n')
            ->join('n', 'category', 'c', 'n.category_id = c.id')
        ;

        $driverOptions = array(
            'qb' => $qb,
            'alias' => 'n',
        );

        $datasource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name');
        $datasource
            ->addField('category', 'text', 'eq', array(
                'field' => 'c.name',
            ))
            ->setMaxResults(8);
        ;

        $parameters = array(
            $datasource->getName() => array(
                DataSourceInterface::PARAMETER_FIELDS => array(
                    'category' => 'name-10',
                ),
            ),
        );

        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();

        $this->assertCount(37, $result);
        $this->assertCount(8, iterator_to_array($result));
    }

    /**
     * Checks DataSource wtih DoctrineDriver using more sophisticated QueryBuilder.
     */
    public function testQueryWithAggregates()
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->connection->createQueryBuilder()
            ->select('c.*')
            ->addSelect('COUNT(n.id) newscount')
            ->from(self::TABLE_CATEGORY_NAME, 'c')
            ->leftJoin('c', 'news', 'n', 'n.category_id = c.id')
            ->groupBy('c.id')
        ;

        $driverOptions = array(
            'qb' => $qb,
            'alias' => 'c',
        );

        $datasource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name');

        $datasource
            ->addField('category', 'text', 'like', array(
                'field' => 'c.name',
            ))
            ->addField('newscount', 'number', 'gt', array(
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having',
            ))
            ->setMaxResults(3)
        ;

        $datasource->bindParameters(array(
            $datasource->getName() => array(
                DataSourceInterface::PARAMETER_FIELDS => array(
                    'newscount' => 3,
                ),
            ),
        ));

        $result = $datasource->getResult();
        $this->assertCount(5, $result);
        $this->assertCount(3, iterator_to_array($result));

        $this->assertEquals(
            $this->testDoctrineExtension->getQueryBuilder()->getSQL(),
            'SELECT c.*, COUNT(n.id) newscount FROM category c LEFT JOIN news n ON n.category_id = c.id GROUP BY c.id HAVING newscount > :newscount LIMIT 3 OFFSET 0'
        );

        $datasource->bindParameters(array(
            $datasource->getName() => array(
                DataSourceInterface::PARAMETER_FIELDS => array(
                    'newscount' => 0,
                ),
            ),
        ));

        $result = $datasource->getResult();
        $this->assertCount(10, $result);
        $this->assertCount(3, iterator_to_array($result));

        $this->assertEquals(
            'SELECT c.*, COUNT(n.id) newscount FROM category c LEFT JOIN news n ON n.category_id = c.id GROUP BY c.id HAVING newscount > :newscount LIMIT 3 OFFSET 0',
            $this->testDoctrineExtension->getQueryBuilder()->getSQL()
        );

        $datasource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name2');
        $datasource
            ->addField('category', 'text', 'like', array(
                'field' => 'c.name',
            ))
            ->addField('newscount', 'number', 'between', array(
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having'
            ))
            ->setMaxResults(2)
        ;

        $parameters = array(
            $datasource->getName() => array(
                DataSourceInterface::PARAMETER_FIELDS => array(
                    'newscount' => array(0, 1),
                ),
            ),
        );

        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();
        $this->assertCount(3, $result);
        $this->assertCount(2, iterator_to_array($result));

        $this->assertEquals(
            'SELECT c.*, COUNT(n.id) newscount FROM category c LEFT JOIN news n ON n.category_id = c.id GROUP BY c.id HAVING newscount BETWEEN :newscount_from AND :newscount_to LIMIT 2 OFFSET 0',
            $this->testDoctrineExtension->getQueryBuilder()->getSQL()
        );
    }

    /**
     * Tests if 'having' value of 'clause' option works properly in 'entity' field
     */
    public function testHavingClauseInEntityField()
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->connection->createQueryBuilder()
            ->select('n')
            ->from(self::TABLE_NEWS_NAME, 'n')
            ->join('n', self::TABLE_CATEGORY_NAME, 'c', 'n.category_id = c.id')
        ;

        $driverOptions = array(
            'qb' => $qb,
            'alias' => 'n'
        );

        $datasource = $dataSourceFactory->createDataSource('doctrine-dbal', $driverOptions, 'name');
        $datasource
            ->addField('category', 'number', 'in', array(
                'clause' => 'having'
            ));

        $parameters = array(
            $datasource->getName() => array(
                DataSourceInterface::PARAMETER_FIELDS => array(
                    'category' => array(2, 3),
                ),
            ),
        );

        $datasource->bindParameters($parameters);
        $datasource->getResult();

        $this->assertEquals(
            'SELECT n FROM news n INNER JOIN category c ON n.category_id = c.id HAVING n.category IN (:dcValue1, :dcValue2)',
            $this->testDoctrineExtension->getQueryBuilder()->getSql()
        );
    }

    private function getNewsDataSource()
    {
        return $this->getDataSourceFactory()->createDataSource(
            'doctrine-dbal',
            array('table' => 'news'),
            'name'
        );
    }
}
