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
use FSi\Component\DataSource\Event\DataSourceEventInterface;
use FSi\Component\DataSource\DataSourceInterface;

/**
 * Class contains method called at GetParameters events.
 */
class GetParameters implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(DataSourceEvents::PRE_GET_PARAMETERS => array('preGetParameters', 128));
    }

    /**
     * Method called at PreGetParameters event.
     *
     * @param DataSourceEventInterface $event
     */
    public function preGetParameters(DataSourceEventInterface $event)
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