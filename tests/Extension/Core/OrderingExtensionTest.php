<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Tests\Extension\Core;

use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Extension\Core\Ordering\Driver\DriverExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Field\FieldAbstractType;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderingExtensionTest extends TestCase
{
    /**
     * Checks DataSource subscriber and storing of passed parameters.
     */
    public function testStoringParameters()
    {
        $extension = new OrderingExtension();
        /** @var MockObject|DataSourceInterface $datasource */
        $datasource = $this->getMockBuilder(DataSourceInterface::class)
            ->getMock();
        /** @var MockObject|FieldTypeInterface $field */
        $field = $this->createMock(FieldTypeInterface::class);
        $fieldExtension = new FieldExtension();

        $field
            ->expects($this->atLeastOnce())
            ->method('getExtensions')
            ->willReturn([$fieldExtension])
        ;

        $datasource
            ->expects($this->any())
            ->method('getFields')
            ->willReturn(['test' => $field])
        ;

        $datasource
            ->expects($this->any())
            ->method('getField')
            ->with('test')
            ->willReturn($field)
        ;

        $datasource
            ->expects($this->any())
            ->method('getName')
            ->willReturn('ds')
        ;

        $subscribers = $extension->loadSubscribers();
        $subscriber = array_shift($subscribers);

        $parameters = ['ds' => [OrderingExtension::PARAMETER_SORT => ['test' => 'asc']]];
        $subscriber->preBindParameters(new DataSourceEvent\ParametersEventArgs($datasource, $parameters));

        //Assert that request parameters are properly stored in FieldExtension.
        $this->assertEquals(
            ['priority' => 0, 'direction' => 'asc'],
            $fieldExtension->getOrdering($field)
        );

        $event = new DataSourceEvent\ParametersEventArgs($datasource, []);
        $subscriber->postGetParameters($event);

        $this->assertEquals($parameters, $event->getParameters());
    }

    /**
     * Each test case consists of fields options definition, ordering parameters passed to datasource
     * and expected fields array which should be sorted in terms of priority of sorting results.
     * Expected array contain sorting passed in parameters first and then default sorting passed in options.
     */
    public function orderingDataProvider()
    {
        return [
            [
                'fields' => [
                    ['name' => 'field1'],
                    ['name' => 'field2'],
                    ['name' => 'field3'],
                ],
                'parameters' => [
                    'field1' => 'asc'
                ],
                'expected_ordering' => [
                    'field1' => 'asc'
                ],
                'expected_parameters' => [
                    'field1' => [
                        'ordering_ascending' => ['field1' => 'asc'],
                        'ordering_descending' => ['field1' => 'desc']
                    ],
                    'field2' => [
                        'ordering_ascending' => [
                            'field2' => 'asc',
                            'field1' => 'asc'
                        ],
                        'ordering_descending' => [
                            'field2' => 'desc',
                            'field1' => 'asc'
                        ]
                    ],
                    'field3' => [
                        'ordering_ascending' => [
                            'field3' => 'asc',
                            'field1' => 'asc'
                        ],
                        'ordering_descending' => [
                            'field3' => 'desc',
                            'field1' => 'asc'
                        ]
                    ],
                ]
            ],
            [
                'fields' => [
                    ['name' => 'field1'],
                    ['name' => 'field2'],
                    ['name' => 'field3'],
                ],
                'parameters' => [
                    'field2' => 'asc',
                    'field1' => 'desc'
                ],
                'expected_ordering' => [
                    'field2' => 'asc',
                    'field1' => 'desc'
                ],
                'expected_parameters' => [
                    'field1' => [
                        'ordering_ascending' => [
                            'field1' => 'asc',
                            'field2' => 'asc'
                        ],
                        'ordering_descending' => [
                            'field1' => 'desc',
                            'field2' => 'asc'
                        ]
                    ],
                    'field2' => [
                        'ordering_ascending' => [
                            'field2' => 'asc',
                            'field1' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field2' => 'desc',
                            'field1' => 'desc'
                        ]
                    ],
                    'field3' => [
                        'ordering_ascending' => [
                            'field3' => 'asc',
                            'field2' => 'asc',
                            'field1' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field3' => 'desc',
                            'field2' => 'asc',
                            'field1' => 'desc'
                        ]
                    ],
                ]
            ],
            [
                'fields' => [
                    [
                        'name' => 'field1',
                        'options' => ['default_sort' => 'asc', 'default_sort_priority' => 1]
                    ],
                    [
                        'name' => 'field2',
                        'options' => ['default_sort' => 'desc', 'default_sort_priority' => 2]
                    ],
                    [
                        'name' => 'field3',
                        'options' => ['default_sort' => 'asc']
                    ],
                ],
                'parameters' => ['field3' => 'desc'],
                'expected_ordering' => [
                    'field3' => 'desc',
                    'field2' => 'desc',
                    'field1' => 'asc'
                ],
                'expected_parameters' => [
                    'field1' => [
                        'ordering_ascending' => [
                            'field1' => 'asc',
                            'field3' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field1' => 'desc',
                            'field3' => 'desc'
                        ]
                    ],
                    'field2' => [
                        'ordering_ascending' => [
                            'field2' => 'asc',
                            'field3' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field2' => 'desc',
                            'field3' => 'desc'
                        ]
                    ],
                    'field3' => [
                        'ordering_ascending' => ['field3' => 'asc'],
                        'ordering_descending' => ['field3' => 'desc']
                    ],
                ]
            ],
            [
                'fields' => [
                    [
                        'name' => 'field1',
                        'options' => ['default_sort' => 'asc', 'default_sort_priority' => 1]
                    ],
                    [
                        'name' => 'field2',
                        'options' => ['default_sort' => 'desc', 'default_sort_priority' => 2]
                    ],
                    [
                        'name' => 'field3',
                        'options' => ['default_sort' => 'asc']
                    ],
                ],
                'parameters' => [
                    'field1' => 'asc',
                    'field3' => 'desc'
                ],
                'expected_ordering' => [
                    'field1' => 'asc',
                    'field3' => 'desc',
                    'field2' => 'desc'
                ],
                'expected_parameters' => [
                    'field1' => [
                        'ordering_ascending' => [
                            'field1' => 'asc',
                            'field3' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field1' => 'desc',
                            'field3' => 'desc'
                        ]
                    ],
                    'field2' => [
                        'ordering_ascending' => [
                            'field2' => 'asc',
                            'field1' => 'asc',
                            'field3' => 'desc'
                        ],
                        'ordering_descending' => [
                            'field2' => 'desc',
                            'field1' => 'asc',
                            'field3' => 'desc'
                        ]
                    ],
                    'field3' => [
                        'ordering_ascending' => [
                            'field3' => 'asc',
                            'field1' => 'asc'
                        ],
                        'ordering_descending' => [
                            'field3' => 'desc',
                            'field1' => 'asc'
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * Checks if sort order is properly calculated from default sorting options and parameters passed from user request.
     * @dataProvider orderingDataProvider
     */
    public function testOrdering(array $fields, array $parameters, array $expectedOrdering, array $expectedParameters)
    {
        $datasource = $this->createMock(DataSourceInterface::class);

        $fieldExtension = new FieldExtension();

        $dataSourceFields = [];
        foreach ($fields as $fieldData) {
            // Using fake class object instead of mock object is helpful
            // because we need functionality from AbstractFieldType.
            $field = new FakeFieldType();
            $field->setName($fieldData['name']);
            $field->setDataSource($datasource);
            $field->addExtension($fieldExtension);
            if (isset($fieldData['options'])) {
                $field->setOptions($fieldData['options']);
            }
            $dataSourceFields[$fieldData['name']] = $field;
        }

        $datasource
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('ds')
        ;

        $datasource
            ->expects($this->any())
            ->method('getFields')
            ->willReturn($fields)
        ;

        $datasource
            ->expects($this->any())
            ->method('getField')
            ->will($this->returnCallback(function () use ($dataSourceFields) {
                return $dataSourceFields[func_get_arg(0)];
            }))
        ;

        $datasource
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(['ds' => [OrderingExtension::PARAMETER_SORT => $parameters]])
        ;

        $extension = new OrderingExtension();
        $subscribers = $extension->loadSubscribers();
        $subscriber = array_shift($subscribers);
        $subscriber->preBindParameters(new DataSourceEvent\ParametersEventArgs(
            $datasource,
            ['ds' => [OrderingExtension::PARAMETER_SORT => $parameters]]
        ));

        //We use fake driver extension instead of specific driver extension because we want to test common DriverExtension functionality.
        $driverExtension = new FakeDriverExtension();
        $result = $driverExtension->sort($dataSourceFields);
        $this->assertSame($expectedOrdering, $result);

        foreach ($dataSourceFields as $field) {
            $view = $this->createMock(FieldViewInterface::class);

            $view
                ->expects($this->exactly(5))
                ->method('setAttribute')
                ->will($this->returnCallback(function ($attribute, $value) use ($field, $parameters, $expectedParameters) {
                    switch ($attribute) {
                        case 'sorted_ascending':
                            $this->assertEquals(
                                (key($parameters) === $field->getName()) && (current($parameters) === 'asc'),
                                $value
                            );
                            break;

                        case 'sorted_descending':
                            $this->assertEquals(
                                (key($parameters) === $field->getName()) && (current($parameters) === 'desc'),
                                $value
                            );
                            break;

                        case 'parameters_sort_ascending':
                            $this->assertSame(
                                [
                                    'ds' => [
                                        OrderingExtension::PARAMETER_SORT => $expectedParameters[$field->getName()]['ordering_ascending']
                                    ]
                                ],
                                $value
                            );
                            break;

                        case 'parameters_sort_descending':
                            $this->assertSame(
                                [
                                    'ds' => [
                                        OrderingExtension::PARAMETER_SORT => $expectedParameters[$field->getName()]['ordering_descending']
                                    ]
                                ],
                                $value
                            );
                            break;
                    }
                }))
            ;

            $fieldExtension->postBuildView(new FieldEvent\ViewEventArgs($field, $view));
        }
    }
}

class FakeFieldType extends FieldAbstractType
{
    public function getType()
    {
        return 'fake';
    }
}

class FakeDriverExtension extends DriverExtension
{
    public function getExtendedDriverTypes()
    {
        return [];
    }

    public function sort(array $fields)
    {
        return $this->sortFields($fields);
    }
}
