<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Ordering\Field;

use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Event\DataSourceFieldEventInterface;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Extension for fields.
 */
class FieldExtension extends FieldAbstractExtension
{
    /**
     * @var array
     */
    private $ordering = array();

    /**
     * {@inheritdoc}
     */
    public function getExtendedFieldTypes()
    {
        return array('text', 'number', 'date', 'time', 'datetime');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FieldEvents::POST_BUILD_VIEW => array('postBuildView')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadOptionsConstraints(OptionsResolverInterface $optionsResolver)
    {
        $optionsResolver->setDefaults(array(
            OrderingExtension::ORDERING_IS_DISABLED => false,
            OrderingExtension::ORDERING => null,
            Orderingextension::ORDERING_PRIORITY => null,
        ));

        $optionsResolver->setAllowedValues(array(
            OrderingExtension::ORDERING => array(null, 'asc', 'desc'),
        ));
    }

    public function setOrdering(FieldTypeInterface $field, $ordering)
    {
        $field_oid = spl_object_hash($field);
        $this->ordering[$field_oid] = $ordering;
    }

    public function getOrdering(FieldTypeInterface $field)
    {
        $field_oid = spl_object_hash($field);
        if (isset($this->ordering[$field_oid]))
            return $this->ordering[$field_oid];
        else
            return null;
    }

    /**
     * {@inheritdoc}
     */
    public function postBuildView(FieldEvent\ViewEventArgs $event)
    {
        $field = $event->getField();
        $field_oid = spl_object_hash($field);
        $view = $event->getView();

        if ($field->hasOption(OrderingExtension::ORDERING_IS_DISABLED) && $field->getOption(OrderingExtension::ORDERING_IS_DISABLED)) {
            $view->setAttribute(OrderingExtension::VIEW_ORDERING_DISABLED, true);
            return;
        }

        $parameters = $field->getDataSource()->getParameters();
        $dataSourceName = $field->getDataSource()->getName();

        if (isset($this->ordering[$field_oid]['direction']) && (key($parameters[$dataSourceName]['ordering']) == $field->getName()))
            $view->setAttribute(OrderingExtension::VIEW_CURRENT_ORDERING, $this->ordering[$field_oid]['direction']);
        else
            $view->setAttribute(OrderingExtension::VIEW_CURRENT_ORDERING, '');

        if (isset($parameters[$dataSourceName][OrderingExtension::ORDERING][$field->getName()]))
            unset($parameters[$dataSourceName][OrderingExtension::ORDERING][$field->getName()]);
        if (!isset($parameters[$dataSourceName][OrderingExtension::ORDERING]))
            $parameters[$dataSourceName][OrderingExtension::ORDERING] = array();
        // little hack: we do not know if PaginationExtension is loaded but if it is we don't want page number in sorting URLs
        unset($parameters[$dataSourceName][PaginationExtension::PAGE]);
        $fields = array_keys($parameters[$dataSourceName]['ordering']);
        array_unshift($fields, $field->getName());
        $directions = array_values($parameters[$dataSourceName][OrderingExtension::ORDERING]);

        $parametersAsc = $parameters;
        $directionsAsc = $directions;
        array_unshift($directionsAsc, 'asc');
        $parametersAsc[$dataSourceName][OrderingExtension::ORDERING] = array_combine($fields, $directionsAsc);
        $view->setAttribute(OrderingExtension::VIEW_ASCENDING_PARAMETERS, $parametersAsc);

        $parametersDesc = $parameters;
        $directionsDesc = $directions;
        array_unshift($directionsDesc, 'desc');
        $parametersDesc[$dataSourceName][OrderingExtension::ORDERING] = array_combine($fields, $directionsDesc);
        $view->setAttribute(OrderingExtension::VIEW_DESCENDING_PARAMETERS, $parametersDesc);
    }
}
