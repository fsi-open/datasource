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
use FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField;
use FSi\Component\DataSource\Driver\DriverAbstractExtension;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Event\DriverEvents;
use FSi\Component\DataSource\Event\DriverEvent;

/**
 * Driver extension for ordering that loads fields extension.
 */
class DoctrineExtension extends DriverAbstractExtension implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedDriverTypes()
    {
        return array('doctrine');
    }

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

    protected function getFieldExtension(DoctrineAbstractField $field)
    {
        $extensions = $field->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension instanceof FieldExtension) {
                return $extension;
            }
        }
        throw new DataSourceException('In order to use ' . __CLASS__ . ' there must be FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension registered in all fields');
    }

    protected function getFieldName(DoctrineAbstractField $field, $alias)
    {
        if ($field->hasOption(DoctrineAbstractField::FIELD_MAPPING)) {
            $name = $field->getOption(DoctrineAbstractField::FIELD_MAPPING);
        } else {
            $name = $field->getName();
        }

        if (!preg_match('/\./', $name)) {
            $name = "$alias.$name";
        }

        return $name;
    }

    public function preGetResult(DriverEvent\DriverEventArgs $event)
    {
        $fields = $event->getFields();
        $orderByFields = array();
        $orderingDirection = array();

        $tmpFields = array();
        foreach ($fields as $field) {
            $fieldExtension = $this->getFieldExtension($field);
            $fieldOrdering = $fieldExtension->getOrdering($field);
            if (isset($fieldOrdering)) {
                $tmpFields[$fieldOrdering['priority']] = $field;
                $orderingDirection[$field->getName()] = $fieldOrdering['direction'];
            }
        }
        ksort($tmpFields);
        foreach ($tmpFields as $field) {
            $orderByFields[$field->getName()] = $field;
        }

        usort($fields, function(FieldTypeInterface $a, FieldTypeInterface $b) {
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

        foreach ($fields as $field) {
            if ($field->hasOption(OrderingExtension::ORDERING) && !isset($orderByFields[$field->getName()])) {
                $orderByFields[$field->getName()] = $field;
            }
        }

        $driver = $event->getDriver();
        $qb = $driver->getQueryBuilder();
        foreach ($orderByFields as $field) {
            $fieldName = $field->getName();
            if (isset($orderingDirection[$fieldName])) {
                $direction = $orderingDirection[$fieldName];
            } elseif ($field->hasOption(OrderingExtension::ORDERING)) {
                $direction = $field->getOption(OrderingExtension::ORDERING);
            } else {
                unset($direction);
            }
            if (isset($direction))
                $qb->addOrderBy($this->getFieldName($field, $driver->getAlias()), $direction);
        }

    }
}
