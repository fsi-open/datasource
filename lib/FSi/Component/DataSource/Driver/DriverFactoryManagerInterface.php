<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver;

interface DriverFactoryManagerInterface
{
    /**
     * @param DriverFactoryInterface $factory
     */
    public function addFactory(DriverFactoryInterface $factory);

    /**
     * @param $driverType
     * @return null
     */
    public function getFactory($driverType);

    /**
     * @param $driverType
     * @return bool
     */
    public function hasFactory($driverType);
}