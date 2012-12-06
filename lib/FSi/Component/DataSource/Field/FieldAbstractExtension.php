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

/**
 * {@inheritdoc}
 */
class FieldAbstractExtension implements FieldExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAvailableOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultAvailableOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultRequiredOptions()
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
    public function loadSubscribers()
    {
        return array();
    }
}