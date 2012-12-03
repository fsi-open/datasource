<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine;

use FSi\Component\DataSource\Field\FieldAbstractType;
use FSi\Component\DataSource\Driver\Doctrine\Exception\DoctrineDriverException;
use Doctrine\ORM\QueryBuilder;

/**
 * {@inheritdoc}
 */
abstract class DoctrineAbstractField extends FieldAbstractType implements DoctrineFieldInterface
{
    const FIELD_MAPPING = 'field_mapping';

    /**
     * {@inheritdoc}
     */
    public function buildQuery(QueryBuilder $qb, $alias)
    {
        $data = $this->getCleanParameter();
        $fieldName = $this->getFieldName($alias);
        $name = $this->getName();

        if (empty($data)) {
            return;
        }

        $comparison = $this->getComparison();
        if (!in_array($comparison, $this->comparisons)) {
            throw new DoctrineDriverException(sprintf('Unexpected comparison type ("%s").', $comparison));
        }

        if ($comparison == 'between') {
            if (!is_array($data)) {
                throw new DoctrineDriverException('Given data must be an array.');
            }

            $from = count($data) ? array_shift($data) : null;
            $to = count($data) ? array_shift($data) : null;

            if (!$from && ($from !== 0)) {
                $from = null;
            }

            if (!$to && ($to !== 0)) {
                $to = null;
            }

            if ($from === null && $to === null) {
                return;
            } elseif ($from === null) {
                $comparison = 'lte';
                $data = $to;
            } elseif ($to === null) {
                $comparison = 'gte';
                $data = $from;
            } else {
                $qb->andWhere($qb->expr()->between($fieldName, $from, $to));
                return;
            }
        }

        switch ($comparison) {
            case 'eq':
            case 'neq':
            case 'lt':
            case 'lte':
            case 'gt':
            case 'gte':
                $qb->andWhere($qb->expr()->$comparison($fieldName, ":$name"));
                $qb->setParameter($this->getName(), $data);
                break;

            case 'like':
                $qb->andWhere($qb->expr()->like($fieldName, ":$name"));
                $qb->setParameter($this->getName(), "%$data%");
                break;

            case 'in':
            case 'notIn':
                if (!is_array($data)) {
                    throw new DoctrineDriverException('Given data must be an array.');
                }
                break;

            default:
                throw new DoctrineDriverException(sprintf('Unexpected comparison type ("%s").', $comparison));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(QueryBuilder $qb, $alias)
    {
        $options = $this->getOptions();
        $fieldName = $this->getFieldName($alias);

        if (isset($options[DoctrineDriver::ORDERING_OPTION])) {
            $name = $this->getName();
            if ($options[DoctrineDriver::ORDERING_OPTION] == 'asc') {
                $qb->addOrderBy($fieldName, 'asc');
            } else {
                $qb->addOrderBy($fieldName, 'desc');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableOptions()
    {
        return array(self::FIELD_MAPPING);
    }

    /**
     * Constructs proper field name from field mapping or (if absent) from own name.
     * Optionally adds alias (if missing).
     *
     * @param string $alias
     * @return string
     */
    protected function getFieldName($alias)
    {
        if ($this->hasOption(self::FIELD_MAPPING)) {
            $name = $this->getOption(self::FIELD_MAPPING);
        } else {
            $name = $this->getName();
        }

        if (!preg_match('/\./', $name)) {
            $name = "$alias.$name";
        }

        return $name;
    }
}
