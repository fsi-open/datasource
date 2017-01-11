<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Query\QueryBuilder;

class Paginator implements \Countable, \IteratorAggregate
{
    /**
     * @var QueryBuilder
     */
    private $query;

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->query->execute()->fetchAll());
    }

    public function count()
    {
        $query = clone $this->query;
        $query->setFirstResult(null);
        $query->setMaxResults(null);

        $sql = $query->getSQL();
        $query->resetQueryParts(array_keys($query->getQueryParts()));

        $query
            ->select('COUNT(*) count')
            ->from(sprintf('(%s)', $sql), 'orig_query')
        ;

        $row = $query->execute()->fetch();
        return $row['count'];
    }
}
