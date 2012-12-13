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

/**
 * Factory for creating drivers.
 */
interface DoctrineFactoryInterface
{
    /**
     * Constructor.
     *
     * As first argument ManagerRegistry is passed, so it's possible to choose
     * which EntityManager has to be passed to driver.
     *
     * @param ManagerRegistry $registry
     * @param array $extensions
     */
    public function __construct(ManagerRegistry $registry, $extensions = array());

    /**
     * Creates driver.
     *
     * @param mixed $entity
     * @param string $alias
     * @param string $entityManager Name of entity manager, not manager itself.
     * @return Doctrine
     */
    public function createDriver($entity, $alias = null, $entityManager = null);
}
