<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Extension\Core;

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Event\DataSourceEvent;
use PHPUnit\Framework\TestCase;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\DataSourceViewInterface;

class PaginationExtensionTest extends TestCase
{
    public function paginationCases(): array
    {
        return [
            [
                'first_result' => 20,
                'max_results' => 20,
                'page' => 2,
                'current_page' => 2
            ],
            [
                'first_result' => 20,
                'max_results' => 0,
                'page' => null,
                'current_page' => 1
            ],
            [
                'first_result' => 0,
                'max_results' => 20,
                'page' => null,
                'current_page' => 1
            ],
        ];
    }

    /**
     * First case of event (when page is not 1).
     * @dataProvider paginationCases
     */
    public function testPaginationExtension(int $firstResult, int $maxResults, ?int $page, int $currentPage): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $extension = new PaginationExtension();

            $datasource = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();

            $datasource->method('getName')->willReturn('datasource');
            $datasource->method('getResult')->willReturn([]);
            $datasource->method('getMaxResults')->willReturn($maxResults);
            $datasource->method('getFirstResult')->willReturn($firstResult);

            $subscribers = $extension->loadSubscribers();
            $subscriber = array_shift($subscribers);
            $event = new DataSourceEvent\ParametersEventArgs($datasource, []);
            $subscriber->postGetParameters($event);

        if (null !== $page) {
            self::assertSame(
                [
                    'datasource' => [
                        PaginationExtension::PARAMETER_MAX_RESULTS => 20,
                        PaginationExtension::PARAMETER_PAGE => 2
                    ]
                ],
                $event->getParameters()
            );
        } else {
            $parameters = $event->getParameters();
            if (isset($parameters['datasource'])) {
                self::assertArrayNotHasKey(PaginationExtension::PARAMETER_PAGE, $parameters['datasource']);
            }
        }

            $view = $this->createMock(DataSourceViewInterface::class);
            $view->method('setAttribute')
                ->willReturnCallback(
                    function ($attribute, $value) use ($currentPage) {
                        if ('page' === $attribute) {
                            self::assertEquals($currentPage, $value);
                        }
                    }
                )
            ;

            $subscriber->postBuildView(new DataSourceEvent\ViewEventArgs($datasource, $view));
    }

    public function testSetMaxResultsByBindRequest(): void
    {
        $extensions = [
            new PaginationExtension()
        ];
        $driverExtensions = [new CoreExtension()];
        $driverFactory = new CollectionFactory($driverExtensions);
        $driverFactoryManager = new DriverFactoryManager([$driverFactory]);
        $factory = new DataSourceFactory($driverFactoryManager, $extensions);
        $dataSource = $factory->createDataSource('collection', [], 'foo_source');

        $dataSource->bindParameters([
            'foo_source' => [
                PaginationExtension::PARAMETER_MAX_RESULTS => 105
            ]
        ]);

        self::assertEquals(105, $dataSource->getMaxResults());
    }
}
