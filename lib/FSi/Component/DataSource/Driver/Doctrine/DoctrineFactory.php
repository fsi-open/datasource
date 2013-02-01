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

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use FSi\Component\DataSource\DataSourceFactoryInterface;

/**
 * {@inheritdoc}
 */
class DoctrineFactory implements DoctrineFactoryInterface
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
     * {@inheritdoc}
     */
    public function __construct(ManagerRegistry $registry, DataSourceFactoryInterface $dataSourceFactory, $extensions = array())
    {
        $this->registry = $registry;
        $this->dataSourceFactory = $dataSourceFactory;
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver($entity, $alias = null, $entityManager = null)
    {
        $entityManager = (string) $entityManager;
        if (empty($entityManager)) {
            $em = $this->registry->getManager($this->registry->getDefaultManagerName());
        } else {
            $em = $this->registry->getManager($entityManager);
        }
        return new DoctrineDriver($this->extensions, $em, $entity, $alias);
    }

    /**
     * {@inheritdoc}
     */
    public function createDataSource($entity, $name = 'datasource', $alias = null, $entityManager = null)
    {
        $driver = $this->createDriver($entity, $alias, $entityManager);
        return $this->dataSourceFactory->createDataSource($driver, $name);
    }
}
