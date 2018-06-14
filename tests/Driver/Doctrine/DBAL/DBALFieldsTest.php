<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL;

use DateTime;
use FSi\Component\DataSource\DataSourceInterface;

class DBALFieldsTest extends TestBase
{
    protected function setUp()
    {
        $this->loadTestData($this->getMemoryConnection());
    }

    public function fieldsProvider(): array
    {
        return [
            [
                'title',
                'text',
                [
                    ['eq', 'title-1', 1],
                    ['neq', 'title-1', 99],
                    ['in', ['title-1', 'title-2'], 2],
                    ['notIn', ['title-1', 'title-2'], 98],
                    ['like', 'title-1', 12],
                    ['contains', 'title-1', 12],
                    ['isNull', 'null', 0],
                ],
            ],
            [
                'id',
                'number',
                [
                    ['eq', 50, 1],
                    ['neq', 50, 99],
                    ['lt', 50, 49],
                    ['lte', 50, 50],
                    ['gt', 50, 50],
                    ['gte', 50, 51],
                    ['in', [50, 60], 2],
                    ['notIn', [50, 60], 98],
                    ['between', ['from' => 30, 'to' => 50], 21],
                    ['isNull', 'null', 0],
                ],
            ],
            [
                'event_date',
                'date',
                [
                    ['eq', new DateTime('1970-01-02 00:00:00'), 24],
                    ['neq', new DateTime('1970-01-01 00:00:00'), 76],
                    ['lt', new DateTime('1970-01-02 00:00:00'), 24],
                    ['lte', new DateTime('1970-01-02 00:00:00'), 48],
                    ['gt', new DateTime('1970-01-02 00:00:00'), 52],
                    ['gte', new DateTime('1970-01-02 00:00:00'), 76],
                    [
                        'in',
                        [
                            new DateTime('1970-01-01 00:00:00'),
                            new DateTime('1970-01-01 01:00:00'),
                            new DateTime('1970-01-02 00:00:00'),
                        ],
                        48 //not 3 because it's date, not datetime
                    ],
                    [
                        'notIn',
                        [
                            new DateTime('1970-01-01 00:00:00'),
                            new DateTime('1970-01-01 01:00:00'),
                            new DateTime('1970-01-02 00:00:00'),
                        ],
                        52 //not 97 because it's date, not datetime
                    ],
                    [
                        'between',
                        [
                            'from' => new DateTime('1970-01-02 00:00:00'),
                            'to' => new DateTime('1970-01-03 00:00:00'),
                        ],
                        48
                    ],
                    ['isNull', 'null', 0],
                ],
            ],
            [
                'create_datetime',
                'datetime',
                [
                    ['eq', new DateTime('1970-01-01 00:00:00'), 1],
                    ['neq', new DateTime('1970-01-01 00:00:00'), 99],
                    ['lt', new DateTime('1970-01-02 00:00:00'), 24],
                    ['lte', new DateTime('1970-01-02 00:00:00'), 25],
                    ['gt', new DateTime('1970-01-02 00:00:00'), 75],
                    ['gte', new DateTime('1970-01-02 00:00:00'), 76],
                    [
                        'in',
                        [
                            new DateTime('1970-01-01 00:00:00'),
                            new DateTime('1970-01-01 01:00:00'),
                        ],
                        2
                    ],
                    [
                        'notIn',
                        [
                            new DateTime('1970-01-01 00:00:00'),
                            new DateTime('1970-01-01 01:00:00'),
                        ],
                        98
                    ],
                    [
                        'between',
                        [
                            'from' => new DateTime('1970-01-01 00:00:00'),
                            'to' => new DateTime('1970-01-02 00:00:00'),
                        ],
                        25
                    ],
                    ['isNull', 'null', 0],
                ],
            ],
            [
                'event_hour',
                'time',
                [
                    ['eq', new DateTime('1970-01-01 01:00:00'), 5],
                    ['neq',new DateTime( '1970-01-01 01:00:00'), 95],
                    ['lt', new DateTime('1970-01-01 03:00:00'), 15],
                    ['lte', new DateTime('1970-01-01 03:00:00'), 20],
                    ['gt', new DateTime('1970-01-02 03:00:00'), 80],
                    ['gte', new DateTime('1970-01-02 03:00:00'), 85],
                    [
                        'in',
                        [
                            new DateTime('1970-01-01 00:00:00'),
                            new DateTime('1970-01-01 01:00:00'),
                            new DateTime('1970-01-02 01:00:00'),
                        ],
                        10 //not 15 because it's time, not datetime
                    ],
                    [
                        'notIn',
                        [
                            new DateTime('1970-01-01 00:00:00'),
                            new DateTime('1970-01-01 01:00:00'),
                            new DateTime('1970-01-02 01:00:00'),
                        ],
                        90 //not 85 because it's time, not datetime
                    ],
                    [
                        'between',
                        [
                            //dates doesn't matter
                            'from' => new DateTime('1970-01-01 02:00:00'),
                            'to' => new DateTime('1970-01-02 05:00:00'),
                        ],
                        18
                    ],
                    ['isNull', 'null', 0],
                ]
            ],
            [
                'visible',
                'boolean',
                [
                    ['eq', true, 50],
                ],
            ],
        ];
    }

    /**
     * @dataProvider fieldsProvider
     */
    public function testFieldResult(string $fieldName, string $datasourceType, array $typeParams): void
    {
        foreach ($typeParams as [$comparison, $parameter, $expectedCount]) {
            $datasource = $this->getNewsDataSource();
            $datasource->addField($fieldName, $datasourceType, $comparison);

            $datasource->bindParameters([
                $datasource->getName() => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        $fieldName => $parameter,
                    ],
                ],
            ]);

            $this->assertCount($expectedCount, $datasource->getResult());
        }
    }

    private function getNewsDataSource()
    {
        return $this->getDataSourceFactory()->createDataSource(
            'doctrine-dbal',
            ['table' => 'news'],
            'name'
        );
    }
}
