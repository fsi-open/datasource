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
class DoctrineExtension extends DriverExtension implements EventSubscriberInterface
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
        $sortedFields = $this->sortFields($fields);

        $driver = $event->getDriver();
        $qb = $driver->getQueryBuilder();
        foreach ($sortedFields as $fieldName => $direction) {
            $field = $fields[$fieldName];
            $qb->addOrderBy($this->getFieldName($field, $driver->getAlias()), $direction);
        }
    }
}
