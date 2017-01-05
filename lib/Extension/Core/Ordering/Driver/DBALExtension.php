<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Ordering\Driver;

use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFieldInterface;
use FSi\Component\DataSource\Event\DriverEvent;
use FSi\Component\DataSource\Event\DriverEvents;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Driver extension for ordering that loads fields extension.
 */
class DBALExtension extends DriverExtension implements EventSubscriberInterface
{
    public function getExtendedDriverTypes()
    {
        return array(
            'doctrine-dbal'
        );
    }

    protected function loadFieldTypesExtensions()
    {
        return array(
            new FieldExtension(),
        );
    }

    public static function getSubscribedEvents()
    {
        return array(
            DriverEvents::PRE_GET_RESULT => array('preGetResult'),
        );
    }

    /**
     * @param FieldTypeInterface $field
     */
    protected function getFieldName($field, $alias)
    {
        if (!$field instanceof DBALFieldInterface) {
            throw new \InvalidArgumentException("Field must be an instance of DoctrineField");
        }

        if ($field->hasOption('field')) {
            $name = $field->getOption('field');
        } else {
            $name = $field->getName();
        }

        if ($field->getOption('auto_alias') && !preg_match('/\./', $name)) {
            $name = "$alias.$name";
        }

        return $name;
    }

    public function preGetResult(DriverEvent\DriverEventArgs $event)
    {
        $fields = $event->getFields();
        $sortedFields = $this->sortFields($fields);

        /** @var DBALDriver $driver */
        $driver = $event->getDriver();

        $qb = $driver->getQueryBuilder();
        foreach ($sortedFields as $fieldName => $direction) {
            $field = $fields[$fieldName];
            $qb->addOrderBy($this->getFieldName($field, $driver->getAlias()), $direction);
        }
    }
}
