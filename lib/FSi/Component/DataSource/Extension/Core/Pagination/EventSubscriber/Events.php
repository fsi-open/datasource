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
            DataSourceEvents::POST_BUILD_VIEW => array('postBuildView', 128),
            DataSourceEvents::PRE_GET_PARAMETERS => array('preGetParameters', 128),
        );
    }

    /**
     * Method called at PostBuildView event.
     *
     * @param DataSourceEventInterface $event
     */
    public function postBuildView(DataSourceEvent\ViewEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $view = $event->getView();

        $view->setAttribute(PaginationExtension::PAGE_PARAM_NAME, sprintf('%s[%s]', $datasource->getName(), DataSourceInterface::PAGE));

        $maxresults = $datasource->getMaxResults();
        if ($maxresults == 0) {
            $all = 1;
        } else {
            $all = ceil(count($datasource->getResult())/$maxresults);
        }

        $params = $datasource->getParameters();
        $datasourceName = $datasource->getName();
        $page = isset($params[$datasourceName]) && isset($params[$datasourceName][DataSourceInterface::PAGE]) ? $params[$datasourceName][DataSourceInterface::PAGE] : 1;
        $view->setAttribute(PaginationExtension::PAGE_AMOUNT, $all);
        $view->setAttribute(PaginationExtension::PAGE_CURRENT, $page);
    }

    /**
     * Method called at PreGetParameters event.
     *
     * @param DataSourceEventInterface $event
     */
    public function preGetParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $data = $event->getParameters();

        $datasourceName = $datasource->getName();
        $maxresults = $datasource->getMaxResults();
        if ($maxresults == 0) {
            $page = 1;
        } else {
            $current = $datasource->getFirstResult();
            $page = floor($current/$maxresults) + 1;
        }

        if ($page != 1) {
            $data[$datasourceName][DataSourceInterface::PAGE] = $page;
            $event->setParameters($data);
        }
    }
}
