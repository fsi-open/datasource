<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Event\DataSourceEvent;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;

/**
 * Event class for DataSource.
 */
class ViewEventArgs extends DataSourceEventArgs
{
    /**
     * @var DataSourceViewInterface
     */
    private $view;

    /**
     * Constructor.
     *
     * @param DataSourceInterface $datasource
     * @param DataSourceViewInterface $view
     */
    public function __construct(DataSourceInterface $datasource, DataSourceViewInterface $view)
    {
        parent::__construct($datasource);
        $this->setView($view);
    }

    /**
     * @return DataSourceViewInterface
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param DataSourceViewInterface $view
     */
    public function setView(DataSourceViewInterface $view)
    {
        $this->view = $view;
    }
}