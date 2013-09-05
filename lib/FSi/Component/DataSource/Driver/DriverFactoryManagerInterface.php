<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver;

interface DriverFactoryManagerInterface
{
    /**
     * @param \FSi\Component\DataSource\Driver\DriverFactoryInterface $factory
     */
    public function addFactory(DriverFactoryInterface $factory);

    /**
     * @param $driverType
     * @return null|\FSi\Component\DataSource\Driver\DriverFactoryInterface
     */
    public function getFactory($driverType);

    /**
     * @param $driverType
     * @return bool
     */
    public function hasFactory($driverType);
}
