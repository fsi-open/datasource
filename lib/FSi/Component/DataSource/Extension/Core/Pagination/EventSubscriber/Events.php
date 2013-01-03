<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Pagination\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\DataSourceInterface;

/**
 * Class contains method called during DataSource events.
 */
class Events implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DataSourceEvents::PRE_BIND_PARAMETERS => 'preBindParameters',
            DataSourceEvents::POST_BUILD_VIEW => 'postBuildView',
        );
    }

    /**
     * Method called at PreBindParameters event.
     *
     * Sets proper page.
     *
     * @param DataSourceEvent\ParametersEventArgs $event
     */
    public function preBindParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $parameters = $event->getParameters();

        $page = isset($parameters[$datasource->getName()][PaginationExtension::PAGE]) ? (int) $parameters[$datasource->getName()][PaginationExtension::PAGE] : 1;
        $datasource->setFirstResult(($page - 1) * $datasource->getMaxResults());
    }

    /**
     * Method called at PostBuildView event.
     *
     * @param DataSourceEvent\ViewEventArgs $event
     */
    public function postBuildView(DataSourceEvent\ViewEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $view = $event->getView();

        $view->setAttribute(PaginationExtension::VIEW_PAGE_PARAM_NAME, sprintf('%s[%s]', $datasource->getName(), PaginationExtension::PAGE));

        $maxresults = $datasource->getMaxResults();
        if ($maxresults == 0) {
            $all = 1;
        } else {
            $all = ceil(count($datasource->getResult())/$maxresults);
        }

        $view->setAttribute(PaginationExtension::VIEW_PAGE_AMOUNT, $all);

        $datasourceName = $datasource->getName();
        $maxresults = $datasource->getMaxResults();
        if ($maxresults == 0) {
            $page = 1;
        } else {
            $current = $datasource->getFirstResult();
            $page = floor($current/$maxresults) + 1;
        }

        $view->setAttribute(PaginationExtension::VIEW_PAGE_CURRENT, $page);
    }
}
