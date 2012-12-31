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
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Event\DataSourceFieldEventInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Extension for fields.
 */
class FieldExtension extends FieldAbstractExtension
{
    /**
     * @var array
     */
    private $givenData = array();

    /**
     * {@inheritdoc}
     */
    public function getExtendedFieldTypes()
    {
        return array('text', 'number', 'date', 'time', 'datetime', 'entity');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FieldEvents::PRE_BIND_PARAMETER => array('preBindParameter', 128),
            FieldEvents::POST_BUILD_VIEW => array('postBuildView', 128),
            FieldEvents::PRE_GET_PARAMETER => array('preGetParameter', 128),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadOptionsConstraints(OptionsResolverInterface $optionsResolver)
    {
        $optionsResolver->setDefaults(array(
            OrderingExtension::ORDERING_IS_GIVEN => false, //Only for internal use.
            OrderingExtension::ORDERING_IS_DISABLED => false,
            OrderingExtension::ORDERING => null,
            Orderingextension::ORDERING_PRIORITY => null,
        ));

        $optionsResolver->setAllowedValues(array(
            OrderingExtension::ORDERING => array(null, 'asc', 'desc'),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function preBindParameter(FieldEvent\ParameterEventArgs $event)
    {
        $field = $event->getField();
        $field_oid = spl_object_hash($field);
        $parameter = $event->getParameter();

        $datasourceName = $field->getDataSource() ? $field->getDataSource()->getName() : null;
        if (empty($datasourceName)) {
            return;
        }

        if ($field->hasOption(OrderingExtension::ORDERING_IS_DISABLED) && $field->getOption(OrderingExtension::ORDERING_IS_DISABLED)) {
            return;
        }

        if (isset($parameter[$datasourceName][OrderingExtension::ORDERING][$field->getName()])) {
            $givenData = $parameter[$datasourceName][OrderingExtension::ORDERING][$field->getName()];
        } else {
            $givenData = array();
        }

        if ((isset($givenData[OrderingExtension::ORDERING]) || isset($givenData[OrderingExtension::ORDERING_PRIORITY]))) {
            $tmp = array();
            $options = $field->getOptions();
            foreach (array(OrderingExtension::ORDERING, OrderingExtension::ORDERING_PRIORITY) as $option) {
                if (isset($givenData[$option])) {
                    $options[$option] = $givenData[$option];
                    $tmp[$option] = $givenData[$option];
                }
            }
            if ($tmp) {
                $options[OrderingExtension::ORDERING_IS_GIVEN] = true;
                $field->setOptions($options);
                $this->givenData[$field_oid] = $tmp;
            } else {
                unset($this->givenData[$field_oid]);
            }
        } else {
            unset($this->givenData[$field_oid]);
        }
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

        $enabled = isset($this->givenData[$field_oid]);
        $options = $field->getOptions();

        $view->setAttribute(OrderingExtension::VIEW_CURRENT_ORDERING, isset($options[OrderingExtension::ORDERING]) ? $options[OrderingExtension::ORDERING] : null);
        $view->setAttribute(OrderingExtension::CURRENT_PRIORITY, isset($options[OrderingExtension::ORDERING_PRIORITY]) ? $options[OrderingExtension::ORDERING_PRIORITY] : null);
        $view->setAttribute(OrderingExtension::VIEW_IS_ENABLED, $enabled);
    }

    /**
     * {@inheritdoc}
     */
    public function preGetParameter(FieldEvent\ParameterEventArgs $event)
    {
        $field = $event->getField();
        $field_oid = spl_object_hash($field);
        $parameter = $event->getParameter();

        if (!isset($this->givenData[$field_oid])) {
            return;
        }

        $datasourceName = $field->getDataSource() ? $field->getDataSource()->getName() : null;
        if (empty($datasourceName)) {
            return;
        }

        $parameter[$datasourceName][OrderingExtension::ORDERING][$field->getName()] = $this->givenData[$field_oid];

        $event->setParameter($parameter);
    }
}
