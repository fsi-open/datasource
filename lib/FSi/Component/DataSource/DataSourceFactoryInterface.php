<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource;

/**
 * Factory maintains building new DataSources with preconfigured extensions.
 */
interface DataSourceFactoryInterface
{
    /**
     * Creates instance of data source with given driver and name.
     *
     * @param Driver\DriverInterface $driver
     * @param string $name
     * @return DataSource
     */
    public function createDataSource(Driver\DriverInterface $driver, $name);

    /**
     * Adds extension to list.
     *
     * @param DataSourceExtensionInterface $extension
     */
    public function addExtension(DataSourceExtensionInterface $extension);

    /**
     * Return array of loaded extensions.
     *
     * @return array
     */
    public function getExtensions();

    /**
     * Return array of all parameters from all datasources.
     *
     * @return array
     */
    public function getAllParameters();

    /**
     * Return array of all parameters form all datasources except given.
     *
     * @param DataSourceInterface $datasource
     * @return array
     */
    public function getOtherParameters(DataSourceInterface $datasource);

    /**
     * Adds given datasource to list of known datasources, so its data will be fetched
     * during getAllParameters and getOtherParameters.
     *
     * Factory also automatically sets its (datasource) factory to itself.
     *
     * @param DataSourceInterface $datasource
     */
    public function addDataSource(DataSourceInterface $datasource);
}
