<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event class for DataSource.
 */
class DataSourceEvent extends Event implements DataSourceEventInterface
{
    private $datasource;

    private $data;

    private $result;

    private $view;

    private $parameters;

    public function setDataSource($datasource)
    {
        $this->datasource = $datasource;
    }

    public function getDataSource()
    {
        return $this->datasource;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setView($view)
    {
        $this->view = $view;
    }

    public function getView()
    {
        return $this->view;
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}
