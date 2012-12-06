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
class DataSourceFieldEvent extends Event implements DataSourceFieldEventInterface
{
    private $parameter;

    private $field;

    private $view;

    public function setParameter($parameter)
    {
        $this->parameter = $parameter;
    }

    public function getParameter()
    {
        return $this->parameter;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setView($view)
    {
        $this->view = $view;
    }

    public function getView()
    {
        return $this->view;
    }
}