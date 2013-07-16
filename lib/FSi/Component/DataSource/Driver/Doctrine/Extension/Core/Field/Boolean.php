<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

/**
 * Boolean field.
 */
class Boolean extends DoctrineAbstractField
{
    /**
     * {@inheritdoc}
     */
    protected $comparisons = array('eq');

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'boolean';
    }

    /**
     * {@inheritdoc}
     */
    public function getDBALType()
    {
        return Type::BOOLEAN;
    }
}
