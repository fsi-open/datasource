<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine;

/**
 * Factory for creating drivers.
 */
interface DoctrineFactoryInterface
{
    /**
     * Creates driver.
     *
     * @param mixed $entity
     * @param string $alias
     * @return Doctrine
     */
    public function createDriver($entity, $alias = null);
}