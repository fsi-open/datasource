<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField;

/**
 * Text field.
 */
class Text extends DoctrineAbstractField
{
    /**
     * {@inheritdoc}
     */
    protected $comparisons = array('eq', 'neq', 'in', 'notIn', 'like', 'contains', 'isNull');

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'text';
    }
}
