<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine;

use Doctrine\ORM\QueryBuilder;

/**
 * Interface for Doctrine driver's fields.
 */
interface DoctrineFieldInterface
{
    /**
     * Builds query.
     *
     * @param QueryBuilder $qb
     * @param string $alias
     */
    public function buildQuery(QueryBuilder $qb, $alias);

    /**
     * Adds ordering to query.
     *
     * @param QueryBuilder $qb
     * @param string $alias
     */
    public function setOrder(QueryBuilder $qb, $alias);
}
