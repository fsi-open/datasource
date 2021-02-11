<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Driver\DriverAbstract;

class DBALDriver extends DriverAbstract
{
    public const DEFAULT_TABLE_ALIAS = 'e';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $table;

    /**
     * Alias, that can be used with preconfigured query when fetching one entity and field mappings
     * don't have mappings prefixed with aliases.
     *
     * @var string
     */
    private $alias;

    /**
     * @var QueryBuilder
     */
    private $initialQuery;

    /**
     * Query builder available during preGetResult event.
     *
     * @var QueryBuilder|null
     */
    private $currentQuery;

    /**
     * @var string|Closure
     */
    private $indexField;

    public function __construct(
        array $extensions,
        Connection $connection,
        $table,
        $alias = null,
        $indexField = null
    ) {
        parent::__construct($extensions);
        $this->connection = $connection;
        $this->table = $table;
        $this->indexField = $indexField;

        if (is_string($alias)) {
            $this->alias = (string) $alias;
        } else {
            $this->alias = self::DEFAULT_TABLE_ALIAS;
        }

        if ($table instanceof QueryBuilder) {
            $this->initialQuery = $table;
        } else {
            if (empty($table)) {
                throw new DBALDriverException('Name of table can\'t be empty.');
            }

            $this->initialQuery = $this->connection->createQueryBuilder();
            $this->initialQuery
                ->select(sprintf('%s.*', $this->alias))
                ->from($this->table, $this->alias)
            ;
        }
    }

    public function getType()
    {
        return 'doctrine-dbal';
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    protected function initResult()
    {
        $this->currentQuery = clone $this->initialQuery;
    }

    protected function buildResult($fields, $first, $max)
    {
        foreach ($fields as $field) {
            if (!$field instanceof DBALFieldInterface) {
                throw new DBALDriverException(
                    'All fields must be instances of FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALFieldInterface.'
                );
            }

            $field->buildQuery($this->currentQuery, $this->alias);
        }

        if ($max > 0) {
            $this->currentQuery->setMaxResults($max);
            $this->currentQuery->setFirstResult($first);
        }

        $paginator = new Paginator($this->currentQuery);

        $this->currentQuery = null;

        return $paginator;
    }

    /**
     * Returns query builder.
     *
     * If query is set to null (so when getResult method is NOT executed at the moment) exception is thrown.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!isset($this->currentQuery)) {
            throw new DBALDriverException('Query is accessible only during preGetResult event.');
        }

        return $this->currentQuery;
    }

    /**
     * @return Closure|string
     */
    public function getIndexField()
    {
        return $this->indexField;
    }
}
