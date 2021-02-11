<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver;

use InvalidArgumentException;

class DriverFactoryManager implements DriverFactoryManagerInterface
{
    /**
     * @var array
     */
    private $factories;

    /**
     * @param array $factories
     * @throws InvalidArgumentException
     */
    public function __construct($factories = [])
    {
        $this->factories = [];

        foreach ($factories as $factory) {
            if (false === $factory instanceof DriverFactoryInterface) {
                throw new InvalidArgumentException(
                    sprintf("Factory must implement %s", DriverFactoryInterface::class)
                );
            }

            $this->addFactory($factory);
        }
    }

    public function addFactory(DriverFactoryInterface $factory)
    {
        $this->factories[$factory->getDriverType()] = $factory;
    }

    /**
     * @param string $driverType
     * @return DriverFactoryInterface|null
     */
    public function getFactory($driverType)
    {
        if ($this->hasFactory($driverType)) {
            return $this->factories[$driverType];
        }

        return null;
    }

    /**
     * @param string $driverType
     * @return bool
     */
    public function hasFactory($driverType)
    {
        return array_key_exists($driverType, $this->factories);
    }
}
