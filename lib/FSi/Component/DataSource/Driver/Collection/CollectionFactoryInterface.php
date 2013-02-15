<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Collection;

use FSi\Component\DataSource\DataSourceFactoryInterface;

/**
 * Factory for creating drivers.
 */
interface CollectionFactoryInterface
{
    /**
     * Constructor.
     *
     * @param DataSourceFactoryInterface $dataSourceFactory
     * @param array $extensions
     */
    public function __construct(DataSourceFactoryInterface $dataSourceFactory, $extensions = array());

    /**
     * Creates driver.
     *
     * @param array $collection
     * @return CollectionDriver
     */
    public function createDriver(array $collection);

    /**
     * Creates new driver and passes it to create new datasource in one step
     * @param array $collection
     * @param string $name
     * @return DataSourceInterface
     */
    public function createDataSource(array $collection, $name);
}
