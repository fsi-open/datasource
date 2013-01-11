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

use FSi\Component\DataSource\Driver\DriverAbstract;
use FSi\Component\DataSource\Driver\Doctrine\DoctrineFieldInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FSi\Component\DataSource\Driver\Doctrine\Exception\DoctrineDriverException;
use Doctrine\ORM\QueryBuilder;
use FSi\Component\DataSource\Extension\Core\Ordering\OrderingExtension;

/**
 * Driver to fetch data from databases using Doctrine.
 */
class DoctrineDriver extends DriverAbstract
{
    /**
     * Arbitrary alias for entity during building query.
     */
    const ENTITY_ALIAS = 'e';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Alias, that can be used with preconfigured query when fetching one entity and field mappings
     * don't have mappings prefixed with aliases.
     *
     * @var string
     */
    private $alias;

    /**
     * Template query builder.
     *
     * @var QueryBuilder
     */
    private $query;

    /**
     * Query builder available during preGetResult event
     *
     * @var QueryBuilder
     */
    private $currentQuery;

    /**
     * Constructor.
     *
     * @throws DoctrineDriverException
     * @param array $extensions
     * @param EntityManager $em
     * @param string|QueryBuilder $entity
     * @param string $alias
     */
    public function __construct($extensions, EntityManager $em, $entity, $alias = null)
    {
        parent::__construct($extensions);

        $this->em = $em;

        if (isset($alias)) {
            $this->alias = (string) $alias;
        } else if ($entity instanceof QueryBuilder) {
            $this->alias = $entity->getRootAlias();
        } else {
            $this->alias = self::ENTITY_ALIAS;
        }

        if ($entity instanceof QueryBuilder) {
            $this->query = $entity;
        } else {
            if (empty($entity)) {
                throw new DoctrineDriverException('Name of entity can\'t be empty.');
            }

            $this->query = $this->em->createQueryBuilder();
            $this->query
                ->select($this->alias)
                ->from((string) $entity, $this->alias)
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'doctrine';
    }

    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function initResult()
    {
        $this->currentQuery = clone $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function buildResult($fields, $first, $max)
    {
        $ordered = array();

        foreach ($fields as $field) {
            if (!$field instanceof DoctrineFieldInterface) {
                throw new DoctrineDriverException(sprintf('All fields must be instances of FSi\Component\DataSource\Driver\Doctrine\DoctrineFieldInterface.'));
            }

            $field->buildQuery($this->currentQuery, $this->alias);
        }

        if ($max > 0) {
            $this->currentQuery->setMaxResults($max);
            $this->currentQuery->setFirstResult($first);
        }

        $result = new Paginator($this->currentQuery);

        $this->currentQuery = null;

        return $result;
    }

    /**
     * Returns query builder.
     *
     * If query is set to null (so when getResult method is NOT executed at the moment) exception is throwed.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!isset($this->currentQuery)) {
            throw new DoctrineDriverException('Query is accessible only during preGetResult event.');
        }

        return $this->currentQuery;
    }
}
