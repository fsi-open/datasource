<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Ordering\Field;

use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;

class FieldExtension extends FieldAbstractExtension
{
    /**
     * @var array
     */
    private $ordering = [];

    public function getExtendedFieldTypes()
    {
        return ['text', 'number', 'date', 'time', 'datetime', 'boolean'];
    }

    public static function getSubscribedEvents()
    {
        return [
            FieldEvents::POST_BUILD_VIEW => ['postBuildView']
        ];
    }

    public function initOptions(FieldTypeInterface $field)
    {
        $field->getOptionsResolver()
            ->setDefined(['default_sort_priority'])
            ->setDefaults([
                'default_sort' => null,
                'sortable' => true
            ])
            ->setAllowedTypes('default_sort_priority', 'integer')
            ->setAllowedTypes('sortable', 'bool')
            ->setAllowedValues('default_sort', [null, 'asc', 'desc']);
        ;
    }

    /**
     * @param FieldTypeInterface $field
     * @param array{priority: string, direction: string}|null $ordering
     */
    public function setOrdering(FieldTypeInterface $field, $ordering)
    {
        $field_oid = spl_object_hash($field);
        $this->ordering[$field_oid] = $ordering;
    }

    /**
     * @param FieldTypeInterface $field
     * @return array{priority: string, direction: string}|null
     */
    public function getOrdering(FieldTypeInterface $field)
    {
        $field_oid = spl_object_hash($field);
        if (isset($this->ordering[$field_oid])) {
            return $this->ordering[$field_oid];
        }

        return null;
    }

    public function postBuildView(FieldEvent\ViewEventArgs $event)
    {
        $field = $event->getField();
        $field_oid = spl_object_hash($field);
        $view = $event->getView();

        $view->setAttribute('sortable', $field->getOption('sortable'));
        if (!$field->getOption('sortable')) {
            return;
        }

        $parameters = $field->getDataSource()->getParameters();
        $dataSourceName = $field->getDataSource()->getName();

        if (
            isset($this->ordering[$field_oid]['direction'])
            && (key($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT]) == $field->getName())
        ) {
            $view->setAttribute('sorted_ascending', $this->ordering[$field_oid]['direction'] == 'asc');
            $view->setAttribute('sorted_descending', $this->ordering[$field_oid]['direction'] == 'desc');
        } else {
            $view->setAttribute('sorted_ascending', false);
            $view->setAttribute('sorted_descending', false);
        }

        if (isset($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT][$field->getName()])) {
            unset($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT][$field->getName()]);
        }

        if (!isset($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT])) {
            $parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT] = [];
        }

        // @FIXME no hacks allowed, resolve this
        // Little hack: we do not know if PaginationExtension is loaded but if
        // it is we don't want page number in sorting URLs.
        unset($parameters[$dataSourceName][PaginationExtension::PARAMETER_PAGE]);
        $fields = array_keys($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT]);
        array_unshift($fields, $field->getName());
        $directions = array_values($parameters[$dataSourceName][OrderingExtension::PARAMETER_SORT]);

        $parametersAsc = $parameters;
        $directionsAsc = $directions;
        array_unshift($directionsAsc, 'asc');
        $parametersAsc[$dataSourceName][OrderingExtension::PARAMETER_SORT] = array_combine($fields, $directionsAsc);
        $view->setAttribute('parameters_sort_ascending', $parametersAsc);

        $parametersDesc = $parameters;
        $directionsDesc = $directions;
        array_unshift($directionsDesc, 'desc');
        $parametersDesc[$dataSourceName][OrderingExtension::PARAMETER_SORT] = array_combine($fields, $directionsDesc);
        $view->setAttribute('parameters_sort_descending', $parametersDesc);
    }
}
