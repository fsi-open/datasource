<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Ordering\Driver;

use FSi\Component\DataSource\Driver\DriverAbstractExtension;
use FSi\Component\DataSource\Event\DriverEvents;
use FSi\Component\DataSource\Extension\Core\Ordering\Field\FieldExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;

abstract class DriverExtension extends DriverAbstractExtension
{
    protected function loadFieldTypesExtensions()
    {
        return [
            new FieldExtension(),
        ];
    }

    public static function getSubscribedEvents()
    {
        return [
            DriverEvents::PRE_GET_RESULT => ['preGetResult'],
        ];
    }

    /**
     * @param FieldTypeInterface $field
     * @return FieldExtension|null
     */
    protected function getFieldExtension(FieldTypeInterface $field)
    {
        $extensions = (array) $field->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension instanceof FieldExtension) {
                return $extension;
            }
        }

        return null;
    }

    /**
     * @param array $fields
     * @return array
     */
    protected function sortFields(array $fields)
    {
        $sortedFields = [];
        $orderingDirection = [];

        $tmpFields = [];
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
        usort($tmpFields, function (FieldTypeInterface $a, FieldTypeInterface $b) {
            switch (true) {
                case $a->hasOption('default_sort') && !$b->hasOption('default_sort'):
                    return -1;

                case !$a->hasOption('default_sort') && $b->hasOption('default_sort'):
                    return 1;

                case $a->hasOption('default_sort') && $b->hasOption('default_sort'):
                    switch (true) {
                        case $a->hasOption('default_sort_priority') && !$b->hasOption('default_sort_priority'):
                            return -1;
                        case !$a->hasOption('default_sort_priority') && $b->hasOption('default_sort_priority'):
                            return 1;
                        case $a->hasOption('default_sort_priority') && $b->hasOption('default_sort_priority'):
                            $aPriority = $a->getOption('default_sort_priority');
                            $bPriority = $b->getOption('default_sort_priority');
                            return ($aPriority != $bPriority) ? (($aPriority > $bPriority) ? -1 : 1) : 0;
                    }
                    // FIXME a default: return 0; should be added to prevent
                    // ambigous fallthrough
                default:
                    return 0;
            }
        });

        foreach ($tmpFields as $field) {
            if ($field->hasOption('default_sort') && !isset($sortedFields[$field->getName()])) {
                $sortedFields[$field->getName()] = $field->getOption('default_sort');
            }
        }

        return $sortedFields;
    }
}
