<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Ordering\Driver;

use FSi\Component\DataSource\Exception\DataSourceException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;
use FSi\Component\DataSource\Event\DriverEvent\DriverEventArgs;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Driver\DriverAbstractExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Event\DriverEvents;
use FSi\Component\DataSource\Event\DriverEvent;

/**
 * Driver extension for ordering that loads fields extension.
 */
abstract class DriverExtension extends DriverAbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadFieldTypesExtensions()
    {
        return array(
            new FieldExtension(),
        );
    }

    public function loadSubscribers()
    {
        return array($this);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DriverEvents::PRE_GET_RESULT => array('preGetResult'),
        );
    }

    protected function getFieldExtension(FieldTypeInterface $field)
    {
        $extensions = $field->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension instanceof FieldExtension) {
                return $extension;
            }
        }
        return null;
    }

    protected function sortFields(array $fields)
    {
        $sortedFields = array();
        $orderingDirection = array();

        $tmpFields = array();
        foreach ($fields as $field) {
            if ($fieldExtension = $this->getFieldExtension($field)) {
                $fieldOrdering = $fieldExtension->getOrdering($field);
                if (isset($fieldOrdering)) {
                    $tmpFields[$fieldOrdering['priority']] = $field;
                    $orderingDirection[$field->getName()] = $fieldOrdering['direction'];
                }
            }
        }
        ksort($tmpFields);
        foreach ($tmpFields as $field) {
            $sortedFields[$field->getName()] = $orderingDirection[$field->getName()];
        }

        $tmpFields = $fields;
        usort($tmpFields, function(FieldTypeInterface $a, FieldTypeInterface $b) {
            switch (true) {
                case $a->hasOption(OrderingExtension::ORDERING) && !$b->hasOption(OrderingExtension::ORDERING):
                    return -1;
                case !$a->hasOption(OrderingExtension::ORDERING) && $b->hasOption(OrderingExtension::ORDERING):
                    return 1;
                case $a->hasOption(OrderingExtension::ORDERING) && $b->hasOption(OrderingExtension::ORDERING):
                    switch (true) {
                        case $a->hasOption(OrderingExtension::ORDERING_PRIORITY) && !$b->hasOption(OrderingExtension::ORDERING_PRIORITY):
                            return -1;
                        case !$a->hasOption(OrderingExtension::ORDERING_PRIORITY) && $b->hasOption(OrderingExtension::ORDERING_PRIORITY):
                            return 1;
                        case $a->hasOption(OrderingExtension::ORDERING_PRIORITY) && $b->hasOption(OrderingExtension::ORDERING_PRIORITY):
                            $aPriority = $a->getOption(OrderingExtension::ORDERING_PRIORITY);
                            $bPriority = $b->getOption(OrderingExtension::ORDERING_PRIORITY);
                            return ($aPriority != $bPriority) ? (($aPriority > $bPriority) ? -1 : 1) : 0;
                    }
                default:
                    return 0;
            }
        });

        foreach ($tmpFields as $field) {
            if ($field->hasOption(OrderingExtension::ORDERING) && !isset($sortedFields[$field->getName()])) {
                $sortedFields[$field->getName()] = $field->getOption(OrderingExtension::ORDERING);
            }
        }

        return $sortedFields;
    }
}
