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
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;

/**
 * Class contains method called during DataSource events.
 */
class Events implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $ordering = array();

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DataSourceEvents::PRE_BIND_PARAMETERS => array('preBindParameters'),
            DataSourceEvents::POST_GET_PARAMETERS => array('postGetParameters'),
        );
    }

    public function preBindParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $datasource_oid = spl_object_hash($datasource);
        $datasourceName = $datasource->getName();
        $parameters = $event->getParameters();

        if (isset($parameters[$datasourceName][OrderingExtension::ORDERING]) && is_array($parameters[$datasourceName][OrderingExtension::ORDERING])) {
            $priority = 0;
            foreach ($parameters[$datasourceName][OrderingExtension::ORDERING] as $fieldName => $direction) {
                $field = $datasource->getField($fieldName);
                $fieldExtension = $this->getFieldExtension($field);
                $fieldExtension->setOrdering($field, array('priority' => $priority, 'direction' => $direction));
                $priority++;
            }
            $this->ordering[$datasource_oid] = $parameters[$datasourceName][OrderingExtension::ORDERING];
        }
    }

    public function postGetParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $datasource_oid = spl_object_hash($datasource);
        $datasourceName = $datasource->getName();
        $parameters = $event->getParameters();

        if (isset($this->ordering[$datasource_oid]))
            $parameters[$datasourceName][OrderingExtension::ORDERING] = $this->ordering[$datasource_oid];

        $event->setParameters($parameters);
    }

    protected function getFieldExtension(FieldTypeInterface $field)
    {
        $extensions = $field->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension instanceof FieldExtension) {
                return $extension;
            }
        }
        throw new DataSourceException('In order to use ' . __CLASS__ . ' there must be FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension registered in all fields');
    }
}
