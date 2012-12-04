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

/**
 * Extension for fields.
 */
class FieldExtension extends FieldAbstractExtension
{
    /**
     * @var array
     */
    private $givenData;

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
    public function getAvailableOptions()
    {
        return array(
            OrderingExtension::ORDERING_IS_GIVEN, //Only for internal use.
            OrderingExtension::ORDERING_IS_DISABLED,
            OrderingExtension::ORDERING_OPTION,
            Orderingextension::ORDERING_PRIORITY_OPTION,
        );
    }

    public function getDefaultAvailableOptions()
    {
        return array(
            OrderingExtension::ORDERING_IS_GIVEN => false,
            OrderingExtension::ORDERING_IS_DISABLED => false,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function preBindParameter(FieldTypeInterface $field, &$parameter)
    {
        $datasourceName = $field->getDataSource() ? $field->getDataSource()->getName() : null;
        if (empty($datasourceName)) {
            return;
        }

        if ($field->hasOption(OrderingExtension::ORDERING_IS_DISABLED) && $field->getOption(OrderingExtension::ORDERING_IS_DISABLED)) {
            return;
        }

        if (
            is_array($parameter)
            && isset($parameter[$datasourceName])
            && isset($parameter[$datasourceName][OrderingExtension::ORDERING])
            && isset($parameter[$datasourceName][OrderingExtension::ORDERING][$field->getName()])
        ) {
            $givenData = $parameter[$datasourceName][OrderingExtension::ORDERING][$field->getName()];
        } else {
            $givenData = array();
        }

        if ((isset($givenData[OrderingExtension::ORDERING_OPTION]) || isset($givenData[OrderingExtension::ORDERING_PRIORITY_OPTION]))) {
            $tmp = array();
            $options = $field->getOptions();
            foreach (array(OrderingExtension::ORDERING_OPTION, OrderingExtension::ORDERING_PRIORITY_OPTION) as $option) {
                if (isset($givenData[$option])) {
                    $options[$option] = $givenData[$option];
                    $tmp[$option] = $givenData[$option];
                }
            }
            if ($tmp) {
                $options[OrderingExtension::ORDERING_IS_GIVEN] = true;
                $field->setOptions($options);
                $this->givenData = $tmp;
            } else {
                $this->givenData = array();
            }
        } else {
            $this->givenData = array();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postBuildView(FieldTypeInterface $field, FieldViewInterface $view)
    {
        if ($field->hasOption(OrderingExtension::ORDERING_IS_DISABLED) && $field->getOption(OrderingExtension::ORDERING_IS_DISABLED)) {
            $view->setOption(OrderingExtension::ORDERING_DISABLED_OPTION, true);
            return;
        }

        $enabled = !empty($this->givenData);
        $options = $field->getOptions();

        $view->setOption(OrderingExtension::CURRENT_ORDERING_OPTION, isset($options[OrderingExtension::ORDERING_OPTION]) ? $options[OrderingExtension::ORDERING_OPTION] : null);
        $view->setOption(OrderingExtension::CURRENT_PRIORITY_OPTION, isset($options[OrderingExtension::ORDERING_PRIORITY_OPTION]) ? $options[OrderingExtension::ORDERING_PRIORITY_OPTION] : null);
        $view->setOption(OrderingExtension::IS_ENABLED_OPTION, $enabled);
    }

    /**
     * {@inheritdoc}
     */
    public function preGetParameter(FieldTypeInterface $field, &$parameter)
    {
        if (empty($this->givenData)) {
            return;
        }

        $datasourceName = $field->getDataSource() ? $field->getDataSource()->getName() : null;
        if (empty($datasourceName)) {
            return;
        }

        $parameter[$datasourceName][OrderingExtension::ORDERING][$field->getName()] = $this->givenData;
    }
}