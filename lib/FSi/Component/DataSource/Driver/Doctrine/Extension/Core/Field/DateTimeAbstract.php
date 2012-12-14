<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField;
use FSi\Component\DataSource\Driver\Doctrine\Exception\DoctrineDriverException;
use Doctrine\ORM\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\Doctrine;

/**
 * General field for datetime related fields.
 */
abstract class DateTimeAbstract extends DoctrineAbstractField
{
    /**
     * {@inheritdoc}
     */
    protected $comparisons = array('eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'in', 'notIn', 'between');

    /**
     * Returns suitable format for comparison when needed.
     *
     * @return string
     */
    abstract protected function getFormat();

    /**
     * {@inheritdoc}
     */
    public function buildQuery(QueryBuilder $qb, $alias)
    {
        $data = $this->getCleanParameter();
        $name = $this->getName();

        if (empty($data)) {
            return;
        }

        $comparison = $this->getComparison();
        if (!in_array($comparison, $this->comparisons)) {
            throw new DoctrineDriverException(sprintf('Unexpected comparison type ("%s").', $comparison));
        }

        $fieldName = $this->getFieldName($alias);

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
                $from = $this->parseValue($from);
                $to = $this->parseValue($to);
                if ($from && $to) {
                    $qb->andWhere($qb->expr()->between($fieldName, ":{$name}_from", ":{$name}_to"));
                    $qb->setParameter("{$name}_from", $from->format($this->getFormat()));
                    $qb->setParameter("{$name}_to", $to->format($this->getFormat()));
                }
                return;
            }
        }

        switch ($comparison) {
            case 'in':
            case 'notIn':
                if (!is_array($data)) {
                    throw new DoctrineDriverException('Given data must be an array.');
                }
                $tmp = array();
                foreach ($data as $value) {
                    $tmp = $this->parseValue($value);
                }
                $data = $tmp;
                array_filter($tmp);

            default:
                $qb->andWhere($qb->expr()->$comparison($fieldName, ":$name"));
                $qb->setParameter($name, $data);
        }
    }

    /**
     * Method to transform values into \DateTime, so we have can be sure what are we parsing.
     *
     * @param mixed $value
     * @return \DateTime
     */
    private function parseValue($value)
    {
        if ($value instanceof \DateTime) {
            return $value;
        } elseif (is_scalar($value)) {
            return new \DateTime((string) $value);
        } else {
            return false;
        }
    }
}