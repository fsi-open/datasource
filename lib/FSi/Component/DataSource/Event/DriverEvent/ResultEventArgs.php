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

use FSi\Component\DataSource\Driver\DriverInterface;

/**
 * Event class for Driver.
 */
class ResultEventArgs extends DriverEventArgs
{
    /**
     * @var mixed
     */
    private $result;

    /**
     * Constructor.
     *
     * @param DriverInterface $driver
     * @param array $fields
     * @param mixed $result
     */
    public function __construct(DriverInterface $driver, array $fields, $result)
    {
        parent::__construct($driver, $fields);
        $this->setResult($result);
    }

    /**
     * @param mixed $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}
