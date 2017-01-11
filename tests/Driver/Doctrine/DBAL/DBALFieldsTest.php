<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFactory;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Extension\Core;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Symfony;
use FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures\DBALDriverExtension;
use FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures\TestConnectionRegistry;
use FSi\Component\DataSource\Tests\Fixtures\DoctrineDriverExtension;

class DBALFieldsTestBase extends TestBase
{
    protected function setUp()
    {
        $this->loadTestData($this->getMemoryConnection());
    }

    public function fieldsProvider()
    {
        return array(
            array(
                'title',
                'text',
                array(
                    array('eq', 'title-1', 1),
                    array('neq', 'title-1', 99),
                    array('in', array('title-1', 'title-2'), 2),
                    array('notIn', array('title-1', 'title-2'), 98),
                    array('like', 'title-1', 12),
                    array('contains', 'title-1', 12),
                    array('isNull', 'null', 0),
                ),
            ),
            array(
                'id',
                'number',
                array(
                    array('eq', 50, 1),
                    array('neq', 50, 99),
                    array('lt', 50, 49),
                    array('lte', 50, 50),
                    array('gt', 50, 50),
                    array('gte', 50, 51),
                    array('in', array(50, 60), 2),
                    array('notIn', array(50, 60), 98),
                    array('between', array('from' => 30, 'to' => 50), 21),
                    array('isNull', 'null', 0),
                ),
            ),
            array(
                'create_date',
                'date',
                array(
                    array('eq', '1970-01-01 00:00:00', 1),
                    array('neq', '1970-01-01 00:00:00', 99),
                    array('lt', '1970-01-02 00:00:00', 25),
                    array('lte', '1970-01-02 00:00:00', 26),
                    array('gt', '1970-01-02 00:00:00', 74),
                    array('gte', '1970-01-02 00:00:00', 75),
                    array('in', array('1970-01-01 00:00:00', '60'), 1),
                    array('notIn', array('1970-01-01 00:00:00', '60'), 99),
                    array('between', array('from' => '1970-01-02 00:00:00', 'to' => '1970-01-03 00:00:00'), 25),
                    array('isNull', 'null', 0),
                ),
            ),
            array(
                'create_date',
                'datetime',
                array(
                    array('eq', '1970-01-01 00:00:00', 1),
                    array('neq', '1970-01-01 00:00:00', 99),
                    array('lt', '1970-01-02 00:00:00', 25),
                    array('lte', '1970-01-02 00:00:00', 26),
                    array('gt', '1970-01-02 00:00:00', 74),
                    array('gte', '1970-01-02 00:00:00', 75),
                    array('in', array('1970-01-01 00:00:00', '60'), 1),
                    array('notIn', array('1970-01-01 00:00:00', '60'), 99),
                    array('between', array('from' => '1970-01-02 00:00:00', 'to' => '1970-01-03 00:00:00'), 25),
                    array('isNull', 'null', 0),
                ),
            ),
            array(
                'create_date',
                'time',
                array(
                    array('eq', '1970-01-01 01:00:00', 1),
                    array('neq', '1970-01-01 01:00:00', 99),
                    array('lt', '1970-01-01 03:00:00', 4),
                    array('lte', '1970-01-01 03:00:00', 5),
                    array('gt', '1970-01-02 03:00:00', 71),
                    array('gte', '1970-01-02 03:00:00', 72),
                    array('in', array('1970-01-01 00:00:00', '60'), 1),
                    array('notIn', array('1970-01-01 00:00:00', '60'), 99),
                    array('between', array('from' => '1970-01-02 02:00:00', 'to' => '1970-01-02 05:00:00'), 4),
                    array('isNull', 'null', 0),
                )
            ),
            array(
                'visible',
                'boolean',
                array(
                    array('eq', true, 50),
                ),
            ),
        );
    }

    /**
     * @dataProvider fieldsProvider
     */
    public function testFieldResult($fieldName, $datasourceType, array $typeParams)
    {
        foreach ($typeParams as $params) {
            list($comparison, $parameter, $expectedCount) = $params;

            $datasource = $this->getNewsDataSource();
            $datasource->addField($fieldName, $datasourceType, $comparison);

            $datasource->bindParameters(array(
                $datasource->getName() => array(
                    DataSourceInterface::PARAMETER_FIELDS => array(
                        $fieldName => $parameter,
                    ),
                ),
            ));

            $this->assertEquals($expectedCount, count($datasource->getResult()));
        }
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
