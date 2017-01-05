<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Interface for DBAL driver's fields.
 */
interface DBALFieldInterface
{
    /**
     * Builds query.
     */
    public function buildQuery(QueryBuilder $qb, $alias);
}
