<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\ORM;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineFactory;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Extension\Core;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Tests\Fixtures\Category;
use FSi\Component\DataSource\Tests\Fixtures\DoctrineDriverExtension;
use FSi\Component\DataSource\Tests\Fixtures\Group;
use FSi\Component\DataSource\Tests\Fixtures\News;
use FSi\Component\DataSource\Tests\Fixtures\TestManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class DoctrineDriverTest extends TestCase
{
    /**
     * @var DoctrineDriverExtension
     */
    protected $testDoctrineExtension;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        //The connection configuration.
        $dbParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config = Setup::createAnnotationMetadataConfiguration(
            [__DIR__ . '/../../../Fixtures'], true, null, null, false
        );
        $em = EntityManager::create($dbParams, $config);
        $tool = new SchemaTool($em);
        $classes = [
            $em->getClassMetadata(News::class),
            $em->getClassMetadata(Category::class),
            $em->getClassMetadata(Group::class),
        ];
        $tool->createSchema($classes);
        $this->load($em);
        $this->em = $em;
    }

    /**
     * Test number field when comparing with 0 value.
     */
    public function testComparingWithZero()
    {
        $datasourceFactory = $this->getDataSourceFactory();
        $driverOptions = [
            'entity' => News::class,
        ];

        $datasource = $datasourceFactory
            ->createDataSource('doctrine-orm', $driverOptions, 'datasource')
            ->addField('id', 'number', 'eq');

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'id' => '0',
                ],
            ],
        ];
        $datasource->bindParameters($parameters);
        $result = $datasource->getResult();
        $this->assertCount(0, $result);
    }

    /**
     * General test for DataSource wtih DoctrineDriver in basic configuration.
     */
    public function testGeneral()
    {
        $datasourceFactory = $this->getDataSourceFactory();
        $datasources = [];
        $driverOptions = [
            'entity' => News::class,
        ];

        $datasources[] = $datasourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from(News::class, 'n');

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'n'
        ];
        $datasources[] = $datasourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource2');

        foreach ($datasources as $datasource) {
            $datasource
                ->addField('title', 'text', 'in')
                ->addField('author', 'text', 'like')
                ->addField('created', 'datetime', 'between', [
                    'field' => 'create_date',
                ])
                ->addField('category', 'entity', 'eq')
                ->addField('category2', 'entity', 'isNull')
                ->addField('not_category2', 'entity', 'neq', [
                    'field' => 'category2'
                ])
                ->addField('group', 'entity', 'memberof', [
                    'field' => 'groups',
                ])
                ->addField('not_group', 'entity', 'notmemberof', [
                    'field' => 'groups',
                ])
                ->addField('tags', 'text', 'isNull', [
                    'field' => 'tags'
                ])
                ->addField('active', 'boolean', 'eq')
            ;

            $result1 = $datasource->getResult();
            $this->assertCount(100, $result1);
            $view1 = $datasource->createView();

            //Checking if result cache works.
            $this->assertSame($result1, $datasource->getResult());

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'author' => 'domain1.com',
                    ],
                ],
            ];
            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();

            //Checking cache.
            $this->assertSame($result2, $datasource->getResult());

            $this->assertCount(50, $result2);
            $this->assertNotSame($result1, $result2);
            unset($result1, $result2);

            $this->assertEquals($parameters, $datasource->getParameters());

            $datasource->setMaxResults(20);
            $parameters = [
                $datasource->getName() => [
                    PaginationExtension::PARAMETER_PAGE => 1,
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            $this->assertCount(100, $result);
            $this->assertCount(20, iterator_to_array($result));

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'author' => 'domain1.com',
                        'title' => ['title44', 'title58'],
                        'created' => ['from' => new DateTime(date('Y:m:d H:i:s', 35 * 24 * 60 * 60))],
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $view = $datasource->createView();
            $result = $datasource->getResult();
            $this->assertCount(2, $result);

            //Checking entity fields. We assume that database was created so first category and first group have ids equal to 1.
            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'group' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            $this->assertCount(25, $result);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'not_group' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            $this->assertCount(75, $result);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'category' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            $this->assertCount(20, $result);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'not_category2' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            $this->assertCount(40, $result);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'group' => 1,
                        'category' => 1,
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            $this->assertCount(5, $result);

            //Checking sorting.
            $parameters = [
                $datasource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'title' => 'asc'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            foreach ($datasource->getResult() as $news) {
                $this->assertEquals('title0', $news->getTitle());
                break;
            }

            //Checking sorting.
            $parameters = [
                $datasource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'title' => 'desc',
                        'author' => 'asc'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            foreach ($datasource->getResult() as $news) {
                $this->assertEquals('title99', $news->getTitle());
                break;
            }

            //checking isnull & notnull
            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'tags' => 'null'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result1 = $datasource->getResult();
            $this->assertCount(50, $result1);
            $ids = [];

            foreach($result1 as $item) {
                $ids[] = $item->getId();
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'tags' => 'notnull'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();
            $this->assertCount(50, $result2);

            foreach($result2 as $item) {
                $this->assertNotContains($item->getId(), $ids);
            }

            unset($result1, $result2);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'category2' => 'null'
                    ],
                ],
            ];

            //checking isnull & notnull - field type entity
            $datasource->bindParameters($parameters);
            $result1 = $datasource->getResult();
            $this->assertCount(50, $result1);
            $ids = [];

            foreach($result1 as $item) {
                $ids[] = $item->getId();
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'category2' => 'notnull'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();
            $this->assertCount(50, $result2);

            foreach($result2 as $item) {
                $this->assertNotContains($item->getId(), $ids);
            }

            unset($result1, $result2);

            //checking - field type boolean
            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => null
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result1 = $datasource->getResult();
            $this->assertCount(100, $result1);

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => 1
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();
            $this->assertCount(50, $result2);
            $ids = [];

            foreach($result2 as $item) {
                $ids[] = $item->getId();
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => 0
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result3 = $datasource->getResult();
            $this->assertCount(50, $result3);

            foreach($result3 as $item) {
                $this->assertNotContains($item->getId(), $ids);
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => true
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result2 = $datasource->getResult();
            $this->assertCount(50, $result2);

            foreach($result2 as $item) {
                $this->assertContains($item->getId(), $ids);
            }

            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'active' => false
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            $result3 = $datasource->getResult();
            $this->assertCount(50, $result3);

            foreach($result3 as $item) {
                $this->assertNotContains($item->getId(), $ids);
            }

            unset($result1, $result2, $result3);

            $parameters = [
                $datasource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'active' => 'desc'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            foreach ($datasource->getResult() as $news) {
                $this->assertTrue($news->isActive());
                break;
            }

            $parameters = [
                $datasource->getName() => [
                    OrderingExtension::PARAMETER_SORT => [
                        'active' => 'asc'
                    ],
                ],
            ];

            $datasource->bindParameters($parameters);
            foreach ($datasource->getResult() as $news) {
                $this->assertFalse($news->isActive());
                break;
            }

            //Test for clearing fields.
            $datasource->clearFields();
            $parameters = [
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'author' => 'domain1.com',
                    ],
                ],
            ];

            //Since there are no fields now, we should have all of entities.
            $datasource->bindParameters($parameters);
            $result = $datasource->getResult();
            $this->assertCount(100, $result);
        }
    }

    /**
     * Checks DataSource wtih DoctrineDriver using more sophisticated QueryBuilder.
     */
    public function testQueryWithJoins()
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from(News::class, 'n')
            ->join('n.category', 'c')
            ->join('n.groups', 'g')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'n'
        ];

        $datasource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $datasource->addField('author', 'text', 'like')
            ->addField('category', 'text', 'like', [
                'field' => 'c.name',
            ])
            ->addField('group', 'text', 'like', [
                'field' => 'g.name',
            ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'group' => 'group0',
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $this->assertCount(25, $datasource->getResult());

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'group' => 'group',
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $this->assertCount(100, $datasource->getResult());

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'group' => 'group0',
                    'category' => 'category0',
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $this->assertCount(5, $datasource->getResult());
    }

    /**
     * Checks DataSource wtih DoctrineDriver using more sophisticated QueryBuilder.
     */
    public function testQueryWithAggregates()
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->em->createQueryBuilder()
            ->select('c', 'COUNT(n) AS newscount')
            ->from(Category::class, 'c')
            ->join('c.news', 'n')
            ->groupBy('c')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'c'
        ];

        $datasource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $datasource
            ->addField('category', 'text', 'like', [
                'field' => 'c.name',
            ])
            ->addField('newscount', 'number', 'gt', [
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having'
            ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => 3,
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->getResult();

        $this->assertEquals(
            $this->testDoctrineExtension->getQueryBuilder()->getQuery()->getDQL(),
            sprintf(
                'SELECT c, COUNT(n) AS newscount FROM %s c INNER JOIN c.news n'
                . ' GROUP BY c HAVING newscount > :newscount',
                Category::class
            )
        );

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => 0,
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->getResult();

        $this->assertEquals(
            $this->testDoctrineExtension->getQueryBuilder()->getQuery()->getDQL(),
            sprintf(
                'SELECT c, COUNT(n) AS newscount FROM %s c INNER JOIN c.news n'
                . ' GROUP BY c HAVING newscount > :newscount',
                Category::class
            )
        );

        $datasource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource2');
        $datasource
            ->addField('category', 'text', 'like', [
                'field' => 'c.name',
            ])
            ->addField('newscount', 'number', 'between', [
                'field' => 'newscount',
                'auto_alias' => false,
                'clause' => 'having'
            ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'newscount' => [0, 1],
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->getResult();

        $this->assertEquals(
            $this->testDoctrineExtension->getQueryBuilder()->getQuery()->getDQL(),
            sprintf(
                'SELECT c, COUNT(n) AS newscount FROM %s c INNER JOIN c.news n'
                . ' GROUP BY c HAVING newscount BETWEEN :newscount_from AND :newscount_to',
                Category::class
            )
        );
    }

    /**
     * Tests if 'having' value of 'clause' option works properly in 'entity' field
     */
    public function testHavingClauseInEntityField()
    {
        $dataSourceFactory = $this->getDataSourceFactory();

        $qb = $this->em->createQueryBuilder()
            ->select('n')
            ->from(News::class, 'n')
            ->join('n.category', 'c')
        ;

        $driverOptions = [
            'qb' => $qb,
            'alias' => 'n'
        ];

        $datasource = $dataSourceFactory->createDataSource('doctrine-orm', $driverOptions, 'datasource');
        $datasource
            ->addField('category', 'entity', 'in', [
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

        $this->assertEquals(
            $this->testDoctrineExtension->getQueryBuilder()->getQuery()->getDQL(),
            sprintf('SELECT n FROM %s n INNER JOIN n.category c HAVING n.category IN (:category)', News::class)
        );
    }

    public function testCreateDriverWithoutEntityAndQbOptions()
    {
        $factory = $this->getDoctrineFactory();
        $this->expectException(InvalidOptionsException::class);
        $factory->createDriver([]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->em);
    }

    private function getDoctrineFactory(): DoctrineFactory
    {
        $this->testDoctrineExtension = new DoctrineDriverExtension();

        $extensions = [
            new CoreExtension(),
            $this->testDoctrineExtension
        ];

        return new DoctrineFactory(new TestManagerRegistry($this->em), $extensions);
    }

    private function getDataSourceFactory(): DataSourceFactory
    {
        $driverFactoryManager = new DriverFactoryManager([
            $this->getDoctrineFactory()
        ]);

        $extensions = [
            new Core\Pagination\PaginationExtension(),
            new OrderingExtension()
        ];

        return new DataSourceFactory($driverFactoryManager, $extensions);
    }

    private function load(EntityManager $em)
    {
        //Injects 5 categories.
        $categories = [];
        for ($i = 0; $i < 5; $i++) {
            $category = new Category();
            $category->setName('category'.$i);
            $em->persist($category);
            $categories[] = $category;
        }

        //Injects 4 groups.
        $groups = [];
        for ($i = 0; $i < 4; $i++) {
            $group = new Group();
            $group->setName('group'.$i);
            $em->persist($group);
            $groups[] = $group;
        }

        //Injects 100 newses.
        for ($i = 0; $i < 100; $i++) {
            $news = new News();
            $news->setTitle('title'.$i);

            //Half of entities will have different author and content.
            if ($i % 2 == 0) {
                $news->setAuthor('author'.$i.'@domain1.com');
                $news->setShortContent('Lorem ipsum.');
                $news->setContent('Content lorem ipsum.');
                $news->setTags('lorem ipsum');
                $news->setCategory2($categories[($i + 1) % 5]);
            } else {
                $news->setAuthor('author'.$i.'@domain2.com');
                $news->setShortContent('Dolor sit amet.');
                $news->setContent('Content dolor sit amet.');
                $news->setActive();
            }

            //Each entity has different date of creation and one of four hours of creation.
            $createDate = new DateTime(date('Y:m:d H:i:s', $i * 24 * 60 * 60));
            $createTime = new DateTime(date('H:i:s', (($i % 4) + 1 ) * 60 * 60));

            $news->setCreateDate($createDate);
            $news->setCreateTime($createTime);

            $news->setCategory($categories[$i % 5]);
            $news->getGroups()->add($groups[$i % 4]);

            $em->persist($news);
        }

        $em->flush();
    }
}
