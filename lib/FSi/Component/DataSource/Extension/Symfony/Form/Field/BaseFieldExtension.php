<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Symfony\Form\Field;

use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;

/**
 * Extension adds some options common for all of fields.
 */
class BaseFieldExtension extends FieldAbstractExtension
{
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
        return array('form_disabled', 'form_label', 'form_options');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultAvailableOptions()
    {
        return array(
            'form_disabled' => false,
        );
    }
}