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
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * {@inheritdoc}
 */
class CollectionFactory implements DriverFactoryInterface
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
     * @var \Symfony\Component\OptionsResolver\OptionsResolver
     */
    private $driverOptionsResolver;

    /**
     * @var \Symfony\Component\OptionsResolver\OptionsResolver
     */
    private $datasourceOptionsResolver;

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
        $this->driverOptionsResolver = new OptionsResolver();
        $this->datasourceOptionsResolver = new OptionsResolver();
        $this->initOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverType()
    {
        return 'collection';
    }

    /**
     * Creates driver.
     *
     * @param array $options
     * @return CollectionDriver
     */
    public function createDriver($options = array())
    {
        $options = $this->driverOptionsResolver->resolve($options);

        return new CollectionDriver($this->extensions, $options['collection']);
    }

    /**
     * Creates new driver and passes it to create new datasource in one step
     * @param array $options
     * @return DataSourceInterface
     */
    public function createDataSource($options = array())
    {
        $options = $this->datasourceOptionsResolver->resolve($options);
        $driver = $this->createDriver(array(
            'collection' => $options['collection']
        ));

        return $this->dataSourceFactory->createDataSource($driver, $options['name']);
    }

    /**
     * Initialize Options Resolvers for driver and datasource builder.
     */
    private function initOptions()
    {
        $this->driverOptionsResolver->setDefaults(array(
            'collection' => array(),
        ));

        $this->driverOptionsResolver->setAllowedTypes(array(
            'collection' => 'array',
        ));

        $this->datasourceOptionsResolver->setDefaults(array(
            'collection' => array(),
            'name' => 'datasource',
        ));

        $this->datasourceOptionsResolver->setAllowedTypes(array(
            'collection' => 'array',
            'name' => 'string',
        ));
    }
}
