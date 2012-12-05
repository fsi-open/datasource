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
     * Key for ordering option.
     */
    const ORDERING = 'ordering';

    /**
     * Key for ordering priority.
     */
    const ORDERING_PRIORITY = 'ordering_priority';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Name of entity to fetch.
     *
     * @var null|string
     */
    private $entity;

    /**
     * Preconfigured query builder, given in constructor.
     *
     * @var QueryBuilder
     */
    private $givenQuery;

    /**
     * Alias, that can be used with preconfigured query when fetching one entity and field mappings
     * don't have mappings prefixed with aliases.
     *
     * @var string
     */
    private $givenAlias;

    /**
     * Reference to query builder during getResult method.
     *
     * @var QueryBuilder
     */
    private $query;

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

        if ($entity instanceof QueryBuilder) {
            $this->givenQuery = $entity;
            if ($alias) {
                $this->givenAlias = (string) $alias;
            }
        } else {
            $this->entity = (string) $entity;
            if (empty($this->entity)) {
                throw new DoctrineDriverException('Name of entity can\'t be empty.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResult($fields, $first, $max)
    {
        $entityAlias = self::ENTITY_ALIAS;
        if (isset($this->givenQuery)) {
            $qb = clone $this->givenQuery;
            if ($this->givenAlias) {
                $entityAlias = $this->givenAlias;
            }
        } else {
            $qb = $this->em->createQueryBuilder();
            $qb
                ->select($entityAlias)
                ->from($this->entity, $entityAlias)
            ;
        }

        $this->query = $qb;

        //preGetResult event.
        foreach ($this->getExtensions() as $extension) {
            $extension->preGetResult($this);
        }

        $ordered = array();
        $orderedEnd = array();

        foreach ($fields as $field) {
            if (!$field instanceof DoctrineFieldInterface) {
                throw new DoctrineDriverException(sprintf('All fields must be instances of FSi\Component\DataSource\Driver\Doctrine\DoctrineFieldInterface.'));
            }

            $field->buildQuery($qb, $entityAlias);

            $options = $field->getOptions();
            if (isset($options[self::ORDERING_PRIORITY])) {
                $ordered[$options[self::ORDERING_PRIORITY]] = $field;
            } else {
                $orderedEnd[] = $field;
            }
        }

        ksort($ordered);
        $ordered = array_reverse($ordered);
        $fields = array_merge($ordered, $orderedEnd);
        foreach ($fields as $field) {
            $field->setOrder($qb, $entityAlias);
        }

        if ($max > 0) {
            $qb->setMaxResults($max);
            $qb->setFirstResult($first);
        }

        //postGetResult event.
        foreach ($this->getExtensions() as $extension) {
            $extension->postGetResult($this);
        }

        //Cleaning query.
        $this->query = null;

        return new Paginator($qb);
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
        if (!isset($this->query)) {
            throw new DoctrineDriverException('Query is accessible only during pre- and postGetResult events.');
        }

        return $this->query;
    }
}