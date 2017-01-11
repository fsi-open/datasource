<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALAbstractField;
use Doctrine\DBAL\Types\Type;

/**
 * Boolean field.
 */
class Boolean extends DBALAbstractField
{
    protected $comparisons = array('eq');

    public function getType()
    {
        return 'boolean';
    }

    public function getDBALType()
    {
        return Type::BOOLEAN;
    }
}
