<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine;

use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Collection\Exception\CollectionDriverException;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Extension\Core;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Tests\Fixtures\Category;
use FSi\Component\DataSource\Tests\Fixtures\Group;
use FSi\Component\DataSource\Tests\Fixtures\News;
use PHPUnit\Framework\TestCase;

class CollectionDriverTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp(): void
    {
        //The connection configuration.
        $dbParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/../../Fixtures'], true, null, null, false);
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

    public function testComparingWithZero(): void
    {
        $datasource = $this->prepareArrayDataSource()
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

    public function testSelectableSource(): void
    {
        $this->driverTests($this->prepareSelectableDataSource());
    }

    public function testArraySource(): void
    {
        $this->driverTests($this->prepareArrayDataSource());
    }

    private function driverTests(DataSource $datasource): void
    {
        $datasource
            ->addField('title', 'text', 'contains')
            ->addField('author', 'text', 'contains')
            ->addField('created', 'datetime', 'between', [
                'field' => 'create_date',
            ])
        ;

        $result1 = $datasource->getResult();
        $this->assertCount(100, $result1);
        $datasource->createView();

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
                    'title' => 'title3',
                    'created' => [
                        'from' => new DateTime(date('Y:m:d H:i:s', 35 * 24 * 60 * 60)),
                    ],
                ],
            ],
        ];
        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        $this->assertCount(2, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'author' => 'author3@domain2.com',
                ],
            ]
        ];
        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        $this->assertCount(1, $result);

        //Checking sorting.
        $parameters = [
            $datasource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'title' => 'desc'
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $this->assertEquals('title99', $datasource->getResult()->first()->getTitle());

        //Checking sorting.
        $parameters = [
            $datasource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'author' => 'asc',
                    'title' => 'desc',
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $this->assertEquals('author99@domain2.com', $datasource->getResult()->first()->getAuthor());

        //Test for clearing fields.
        $datasource->clearFields();
        $datasource->setMaxResults(null);
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

        //Test boolean field
        $datasource
            ->addField('active', 'boolean', 'eq')
        ;
        $datasource->setMaxResults(null);
        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => 1,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        $this->assertCount(50, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => 0,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        $this->assertCount(50, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => true,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        $this->assertCount(50, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => false,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        $this->assertCount(50, $result);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'active' => null,
                ],
            ]
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        $this->assertCount(100, $result);

        $parameters = [
            $datasource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'active' => 'desc'
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $this->assertFalse($datasource->getResult()->first()->isActive());

        $parameters = [
            $datasource->getName() => [
                OrderingExtension::PARAMETER_SORT => [
                    'active' => 'asc'
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $this->assertFalse(false, $datasource->getResult()->first()->isActive());


        // test 'notIn' comparison
        $datasource->addField('title_is_not', 'text', 'notIn', [
            'field' => 'title',
        ]);

        $parameters = [
            $datasource->getName() => [
                DataSourceInterface::PARAMETER_FIELDS => [
                    'title_is_not' => ['title1', 'title2', 'title3']
                ],
            ],
        ];

        $datasource->bindParameters($parameters);
        $datasource->createView();
        $result = $datasource->getResult();
        $this->assertCount(97, $result);
    }

    public function testExceptions(): void
    {
        $datasource = $this->prepareArrayDataSource();
        $field = $this->createMock(FieldTypeInterface::class);

        $field
            ->expects($this->any())
            ->method('getName')
            ->willReturn('example')
        ;

        $datasource->addField($field);

        $this->expectException(CollectionDriverException::class);
        $datasource->getResult();
    }

    public function testExceptions2(): void
    {
        $driverFactory = $this->getCollectionFactory();
        $driver = $driverFactory->createDriver();

        $this->expectException(CollectionDriverException::class);
        $driver->getCriteria();
    }

    protected function tearDown(): void
    {
        unset($this->em);
    }

    private function getCollectionFactory(): CollectionFactory
    {
        $extensions = [
            new CoreExtension(),
        ];

        return new CollectionFactory($extensions);
    }

    private function getDataSourceFactory(): DataSourceFactory
    {
        $driverFactoryManager = new DriverFactoryManager([
            $this->getCollectionFactory()
        ]);

        $extensions = [
            new Core\Pagination\PaginationExtension(),
            new OrderingExtension(),
        ];

        return new DataSourceFactory($driverFactoryManager, $extensions);
    }

    private function prepareSelectableDataSource(): DataSource
    {
        $driverOptions = [
            'collection' => $this->em->getRepository(News::class),
            'criteria' => Criteria::create()->orderBy(['title' => Criteria::ASC]),
        ];

        return $this->getDataSourceFactory()->createDataSource('collection', $driverOptions, 'datasource1');
    }

    private function prepareArrayDataSource(): DataSource
    {
        $driverOptions = [
            'collection' => $this->em
                ->createQueryBuilder()
                ->select('n')
                ->from(News::class, 'n')
                ->getQuery()
                ->execute(),
            'criteria' => Criteria::create()->orderBy(['title' => Criteria::ASC]),
        ];

        return $this->getDataSourceFactory()->createDataSource('collection', $driverOptions, 'datasource2');
    }

    private function load(EntityManagerInterface $em): void
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
            if ($i % 2 === 0) {
                $news->setAuthor('author'.$i.'@domain1.com');
                $news->setShortContent('Lorem ipsum.');
                $news->setContent('Content lorem ipsum.');
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
