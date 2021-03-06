<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field;

use Doctrine\DBAL\Types\Type;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALAbstractField;

/**
 * Time field.
 */
class Time extends DBALAbstractField
{
    protected $comparisons = ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'in', 'notIn', 'between', 'isNull'];

    public function getType()
    {
        return 'time';
    }

    public function getDBALType()
    {
        return Type::TIME;
    }
}
