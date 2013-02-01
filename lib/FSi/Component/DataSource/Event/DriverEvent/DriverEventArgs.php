<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Event\DriverEvent;

use Symfony\Component\EventDispatcher\Event;
use FSi\Component\DataSource\Driver\DriverInterface;

/**
 * Event class for Driver.
 */
class DriverEventArgs extends Event
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var array
     */
    private $fields;

    /**
     * Constructor.
     *
     * @param DriverInterface $driver
     * @param array $fields
     */
    public function __construct(DriverInterface $driver, array $fields)
    {
        $this->driver = $driver;
        $this->fields = $fields;
    }

    /**
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}
