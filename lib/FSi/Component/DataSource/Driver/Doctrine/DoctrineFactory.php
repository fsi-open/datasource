<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use FSi\Component\DataSource\DataSourceFactoryInterface;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * {@inheritdoc}
 */
class DoctrineFactory implements DriverFactoryInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

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
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry, DataSourceFactoryInterface $dataSourceFactory, $extensions = array())
    {
        $this->registry = $registry;
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
        return 'doctrine';
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver($options = array())
    {
        $options = $this->driverOptionsResolver->resolve($options);

        if (empty($entityManager)) {
            $em = $this->registry->getManager($this->registry->getDefaultManagerName());
        } else {
            $em = $this->registry->getManager($options['em']);
        }

        return new DoctrineDriver($this->extensions, $em, $options['entity'], $options['alias']);
    }

    /**
     * {@inheritdoc}
     */
    public function createDataSource($options = array())
    {
        $options = $this->datasourceOptionsResolver->resolve($options);

        $driver = $this->createDriver(array(
            'entity' => $options['entity'],
            'alias' => $options['alias'],
            'em' => $options['em']
        ));

        return $this->dataSourceFactory->createDataSource($driver, $options['name']);
    }

    /**
     * Initialize Options Resolvers for driver and datasource builder.
     */
    private function initOptions()
    {
        $this->driverOptionsResolver->setRequired(array(
            'entity'
        ));

        $this->driverOptionsResolver->setDefaults(array(
            'alias' => null,
            'em' => null
        ));

        $this->driverOptionsResolver->setAllowedTypes(array(
            'entity' => array('string', '\Doctrine\ORM\QueryBuilder'),
            'alias' => array('null', 'string'),
            'em' => array('null', 'string')
        ));

        $this->datasourceOptionsResolver->setRequired(array(
            'entity'
        ));

        $this->datasourceOptionsResolver->setDefaults(array(
            'name' => 'datasource',
            'alias' => null,
            'em' => null
        ));

        $this->datasourceOptionsResolver->setAllowedTypes(array(
            'entity' => array('string', '\Doctrine\ORM\QueryBuilder'),
            'name' => 'string',
            'alias' => array('null', 'string'),
            'em' => array('null', 'string')
        ));
    }
}
