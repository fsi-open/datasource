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
 * Class contains method called during DataSource events.
 */
class Events implements EventSubscriberInterface
{
    /**
     * @var int
     */
    private $nextPriority;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DataSourceEvents::PRE_BIND_PARAMETERS => array('preBindParameters', 128),
            DataSourceEvents::PRE_GET_RESULT => array('preGetResult', 128),
            DataSourceEvents::POST_BUILD_VIEW => array('postBuildView', 128),
        );
    }

    /**
     * Method called at PreBindParameters event.
     *
     * @param DataSourceEvent\ParametersEventArgs $event
     */
    public function preBindParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $parameterssource = $event->getDataSource();
        $parameters = $event->getParameters();

        $parameterssourceName = $parameterssource->getName();
    }

    /**
     * Method called at PreGetResult event.
     *
     * @param DataSourceEvent\DataSourceEventArgs $event
     */
    public function preGetResult(DataSourceEvent\DataSourceEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $this->countNextPriority($datasource);
        $resultBasic = array();
        $endBasic = array();
        $resultGiven = array();
        $endGiven = array();

        foreach ($datasource->getFields() as $field) {
            if ($field->hasOption(OrderingExtension::ORDERING_IS_GIVEN) && $field->getOption(OrderingExtension::ORDERING_IS_GIVEN)) {
                $result = &$resultGiven;
                $end = &$endGiven;
            } else {
                $result = &$resultBasic;
                $end = &$endBasic;
            }

            $options = $field->getOptions();
            if (isset($options[OrderingExtension::ORDERING_PRIORITY])) {
                $priority = (int) $options[OrderingExtension::ORDERING_PRIORITY];
            } else {
                $end[] = array('field' => $field);
                continue;
            }

            $i = 0;
            foreach ($result as $item) {
                if ($item['priority'] < $priority) {
                    break;
                }
                $i++;
            }

            array_splice($result, $i, 0, array(array('priority' => $priority, 'field' => $field)));
        }

        $fields = array_merge($resultGiven, $endGiven, $resultBasic, $endBasic);

        $max = count($fields);
        foreach ($fields as $item) {
            $field = $item['field'];
            $options = $field->getOptions();
            $options[OrderingExtension::ORDERING_PRIORITY] = $max;
            $field->setOptions($options);
            $max--;
        }
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

        $this->countNextPriority($datasource);
        $view->setAttribute(OrderingExtension::VIEW_NEXT_PRIORITY, $this->nextPriority);

        $datasourceName = $datasource->getName();
        $view->setAttribute(OrderingExtension::VIEW_PATTERN_ORDERING, sprintf(OrderingExtension::PATTERN, $datasourceName, OrderingExtension::ORDERING, '%s', OrderingExtension::ORDERING));
        $view->setAttribute(OrderingExtension::VIEW_PATTERN_PRIORITY, sprintf(OrderingExtension::PATTERN, $datasourceName, OrderingExtension::ORDERING, '%s', OrderingExtension::ORDERING_PRIORITY));
    }

    /**
     * Counts next priority for orderings.
     *
     * @param DataSourceInterface $datasource
     */
    private function countNextPriority($datasource)
    {
        if (isset($this->nextPriority)) {
            return;
        }

        $next = 0;
        foreach ($datasource->getFields() as $field) {
            if ($field->hasOption(OrderingExtension::ORDERING_IS_GIVEN) && $field->getOption(OrderingExtension::ORDERING_IS_GIVEN) && $field->hasOption(OrderingExtension::ORDERING_PRIORITY)) {
                $tmp = (int) $field->getOption(OrderingExtension::ORDERING_PRIORITY);
                if ($tmp > $next) {
                    $next = $tmp;
                }
            }
        }
        $this->nextPriority = floor($next) + 1;
    }
}
