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
 * Extension of DataSources field.
 */
interface FieldExtensionInterface extends EventSubscriberInterface
{
    /**
     * Returns array of extended types.
     *
     * @return array
     */
    public function getExtendedFieldTypes();

    /**
     * Allows extension to load options' constraints to fields OptionsResolver. Called by field.
     *
     * @param FieldTypeInterface $field
     */
    public function initOptions(FieldTypeInterface $field);
}
