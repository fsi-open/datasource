<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField;

/**
 * Date field.
 */
class Date extends DoctrineAbstractField
{
    /**
     * {@inheritdoc}
     */
    protected $comparisons = array('eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'in', 'notIn', 'between', 'isNull');

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'date';
    }
}
