<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Ordering\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;

/**
 * Class contains method called at BindParameters events.
 */
class BindParameters implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $resetPage = false;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DataSourceEvents::PRE_BIND_PARAMETERS => array('preBindParameters', 128),
            DataSourceEvents::POST_BIND_PARAMETERS => array('postBindParameters', 128),
        );
    }

    /**
     * Method called at PreBindParameters event.
     *
     * @param DataSourceEventInterface $event
     */
    public function preBindParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $parameterssource = $event->getDataSource();
        $parameters = $event->getParameters();

        $parameterssourceName = $parameterssource->getName();

        if (
            isset($parameters[$parameterssourceName])
            && isset($parameters[$parameterssourceName][OrderingExtension::ORDERING])
            && isset($parameters[$parameterssourceName][OrderingExtension::ORDERING][OrderingExtension::RESET_PAGE])
        ) {
            unset($parameters[$parameterssourceName][OrderingExtension::ORDERING][OrderingExtension::RESET_PAGE]);
            $event->setParameters($parameters);
            $this->resetPage = true;
        }
    }

    /**
     * Method called at PostBindParameters event.
     *
     * @param DataSourceEventInterface $event
     */
    public function postBindParameters(DataSourceEvent\DataSourceEventArgs $event)
    {
        if ($this->resetPage) {
            $event->getDataSource()->setFirstResult(0);
        }
    }
}
