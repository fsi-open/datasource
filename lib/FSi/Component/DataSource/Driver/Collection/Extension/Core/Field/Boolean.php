<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Collection\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Collection\CollectionAbstractField;
use Doctrine\Common\Collections\Criteria;

/**
 * Boolean field.
 */
class Boolean extends CollectionAbstractField
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
    public function buildCriteria(Criteria $c)
    {
        $data = $this->getCleanParameter();

        if (empty($data) && ($data !== 0 && $data !== false)) {
            return;
        }

        $field = $this->hasOption('field')
            ? $this->getOption('field')
            : $this->getName();
        $comparison = $this->getComparison();
        $eb = Criteria::expr();

        // need to convert 1 and 0 to boolean, or the comparison will fail
        $c->andWhere($eb->$comparison($field, (boolean) $data));
    }
}