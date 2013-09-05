<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * {@inheritdoc}
 */
class FieldAbstractExtension implements FieldExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedFieldTypes()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function initOptions(FieldTypeInterface $field)
    {
    }
}
