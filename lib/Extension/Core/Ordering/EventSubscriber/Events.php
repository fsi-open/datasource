<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Ordering\EventSubscriber;

use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Field\FieldExtensionInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Events implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $ordering = [];

    public static function getSubscribedEvents()
    {
        return [
            DataSourceEvents::PRE_BIND_PARAMETERS => ['preBindParameters'],
            DataSourceEvents::POST_GET_PARAMETERS => ['postGetParameters'],
        ];
    }

    public function preBindParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $datasource_oid = spl_object_hash($datasource);
        $datasourceName = $datasource->getName();
        $parameters = $event->getParameters();

        if (
            isset($parameters[$datasourceName][OrderingExtension::PARAMETER_SORT])
            && is_array($parameters[$datasourceName][OrderingExtension::PARAMETER_SORT])
        ) {
            $priority = 0;
            foreach ($parameters[$datasourceName][OrderingExtension::PARAMETER_SORT] as $fieldName => $direction) {
                if (false === in_array($direction, ['asc', 'desc'])) {
                    throw new DataSourceException(sprintf("Unknown sorting direction %s specified", $direction));
                }

                $field = $datasource->getField($fieldName);
                $fieldExtension = $this->getFieldExtension($field);
                $fieldExtension->setOrdering($field, ['priority' => $priority, 'direction' => $direction]);
                $priority++;
            }

            $this->ordering[$datasource_oid] = $parameters[$datasourceName][OrderingExtension::PARAMETER_SORT];
        }
    }

    public function postGetParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $datasource_oid = spl_object_hash($datasource);
        $datasourceName = $datasource->getName();
        $parameters = $event->getParameters();

        if (isset($this->ordering[$datasource_oid])) {
            $parameters[$datasourceName][OrderingExtension::PARAMETER_SORT] = $this->ordering[$datasource_oid];
        }

        $event->setParameters($parameters);
    }

    /**
     * @param FieldTypeInterface $field
     * @return FieldExtension
     * @throws DataSourceException
     */
    protected function getFieldExtension(FieldTypeInterface $field)
    {
        $extensions = $field->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension instanceof FieldExtension) {
                return $extension;
            }
        }

        throw new DataSourceException(sprintf(
            'In order to use %s there must be %s registered in all fields',
            __CLASS__,
            FieldExtension::class
        ));
    }
}
