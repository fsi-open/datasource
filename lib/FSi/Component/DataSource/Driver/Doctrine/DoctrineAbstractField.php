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

use FSi\Component\DataSource\Field\FieldAbstractType;
use FSi\Component\DataSource\Driver\Doctrine\Exception\DoctrineDriverException;
use Doctrine\ORM\QueryBuilder;

/**
 * {@inheritdoc}
 */
abstract class DoctrineAbstractField extends FieldAbstractType implements DoctrineFieldInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildQuery(QueryBuilder $qb, $alias)
    {
        $data = $this->getCleanParameter();
        $fieldName = $this->getFieldName($alias);
        $name = $this->getName();

        if (empty($data) && ($data !== 0)) {
            return;
        }

        $comparison = $this->getComparison();
        if (!in_array($comparison, $this->comparisons)) {
            throw new DoctrineDriverException(sprintf('Unexpected comparison type ("%s").', $comparison));
        }

        $func = sprintf('and%s', ucfirst($this->getOption('clause')));

        if ($comparison == 'between') {
            if (!is_array($data)) {
                throw new DoctrineDriverException('Given data must be an array.');
            }

            $from = array_shift($data);
            $to = array_shift($data);

            if (empty($from) && ($from !== 0)) {
                $from = null;
            }

            if (empty($to) && ($to !== 0)) {
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
                $qb->$func($qb->expr()->between($fieldName, ":{$name}_from", ":{$name}_to"));
                $qb->setParameter("{$name}_from", $from);
                $qb->setParameter("{$name}_to", $to);
                return;
            }
        }

        switch ($comparison) {
            case 'in':
            case 'notIn':
                if (!is_array($data)) {
                    throw new DoctrineDriverException('Fields with \'in\' and \'notIn\' comparisons require to bind an array.');
                }
            case 'eq':
            case 'neq':
            case 'lt':
            case 'lte':
            case 'gt':
            case 'gte':
                $qb->$func($qb->expr()->$comparison($fieldName, ":$name"));
                $qb->setParameter($this->getName(), $data);
                break;

            case 'like':
            case 'contains':
                $qb->$func($qb->expr()->like($fieldName, ":$name"));
                $qb->setParameter($this->getName(), "%$data%");
                break;

            case 'isNull':
                $qb->$func($fieldName . ' IS ' . ($data === 'null' ? '' : 'NOT ') . 'NULL');
                break;

            default:
                throw new DoctrineDriverException(sprintf('Unexpected comparison type ("%s").', $comparison));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initOptions()
    {
        $field = $this;
        $this->getOptionsResolver()
            ->setOptional(array('field'))
            ->setDefaults(array(
                'auto_alias' => true,
                'clause' => 'where'
            ))
            ->setAllowedValues(array(
                'clause' => array('where', 'having')
            ))
            ->setAllowedTypes(array(
                'field' => array('string', 'null'),
                'auto_alias' => 'bool'
            ))
            ->setNormalizers(array(
                'field' => function($options, $value) use ($field) {
                    if (!isset($value) && $field->getName()) {
                        return $field->getName();
                    } else {
                        return $value;
                    }
                },
                'clause' => function($options, $value) {
                    return strtolower($value);
                }
            ))
        ;
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
        $name = $this->getOption('field');

        if ($this->getOption('auto_alias') && !preg_match('/\./', $name)) {
            $name = "$alias.$name";
        }

        return $name;
    }
}
