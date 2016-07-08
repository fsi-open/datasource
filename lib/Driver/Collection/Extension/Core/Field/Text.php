<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Collection\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Collection\CollectionAbstractField;

/**
 * Text field.
 */
class Text extends CollectionAbstractField
{
    /**
     * {@inheritdoc}
     *
     * 'nin' comparison is deprecated since 1.3 and will be removed in 2.0
     */
    protected $comparisons = array('eq', 'neq', 'in', 'nin', 'notIn', 'contains');

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'text';
    }
}
