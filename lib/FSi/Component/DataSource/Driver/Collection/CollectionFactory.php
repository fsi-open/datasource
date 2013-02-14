<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Lukasz Cybula <lukasz@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Collection;

use FSi\Component\DataSource\DataSourceFactoryInterface;

/**
 * {@inheritdoc}
 */
class CollectionFactory implements CollectionFactoryInterface
{
    /**
     * @var DataSourceFactoryInterface
     */
    private $dataSourceFactory;

    /**
     * Array of extensions.
     *
     * @var array
     */
    private $extensions;

    /**
     * Constructor.
     *
     * @param DataSourceFactoryInterface $dataSourceFactory
     * @param array $extensions
     */
    public function __construct(DataSourceFactoryInterface $dataSourceFactory, $extensions = array())
    {
        $this->dataSourceFactory = $dataSourceFactory;
        $this->extensions = $extensions;
    }

    /**
     * Creates driver.
     *
     * @param array $collection
     * @return CollectionDriver
     */
    public function createDriver(array $collection)
    {
        return new CollectionDriver($this->extensions, $collection);
    }

    /**
     * Creates new driver and passes it to create new datasource in one step
     * @param array $collection
     * @param string $name
     * @return DataSourceInterface
     */
    public function createDataSource(array $collection, $name = 'datasource')
    {
        $driver = $this->createDriver($collection);
        return $this->dataSourceFactory->createDataSource($driver, $name);
    }
}
