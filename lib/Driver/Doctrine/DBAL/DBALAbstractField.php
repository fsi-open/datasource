<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Exception\DBALDriverException;
use FSi\Component\DataSource\Field\FieldAbstractType;

abstract class DBALAbstractField extends FieldAbstractType implements DBALFieldInterface
{
    public function buildQuery(QueryBuilder $qb, $alias)
    {
        $data = $this->getCleanParameter();
        $fieldName = $this->getFieldName($alias);
        $name = $this->getName();

        if (($data === array()) || ($data === '') || ($data === null)) {
            return;
        }

        $type = $this->getDBALType();
        $comparison = $this->getComparison();

        $clause = $this->getOption('clause');
        $func = sprintf('and%s', ucfirst($clause));

        if ($comparison == 'between') {
            if (!is_array($data)) {
                throw new DBALDriverException('Fields with \'between\' comparison require to bind an array.');
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
                $qb->$func("$fieldName BETWEEN :{$name}_from AND :{$name}_to");
                $qb->setParameter("{$name}_from", $from, $type);
                $qb->setParameter("{$name}_to", $to, $type);
                return;
            }
        }

        if ($comparison == 'isNull') {
            $qb->$func($fieldName . ' IS ' . ($data === 'null' ? '' : 'NOT ') . 'NULL');
            return;
        }

        $expr = $qb->expr();
        if (in_array($comparison, array('in', 'notIn'))) {
            if (!is_array($data)) {
                throw new DBALDriverException('Fields with \'in\' and \'notIn\' comparisons require to bind an array.');
            }
            $placeholders = array();
            foreach ($data as $value) {
                $placeholders[] = $qb->createNamedParameter($value);
            }
            //this is because "in" and "notIn" was added in DBAL 2.4
            $comparison = $comparison === 'in' ? 'IN' : 'NOT IN';
            $qb->$func($expr->comparison($fieldName, $comparison, '('.implode(', ', $placeholders).')'));
            return;
        } elseif (in_array($comparison, array('like', 'contains'))) {
            $data = "%$data%";
            $comparison = 'like';
        }

        $qb->$func($expr->$comparison($fieldName, ":$name"));
        $qb->setParameter($this->getName(), $data, $type);
    }

    public function initOptions()
    {
        $field = $this;
        $this->getOptionsResolver()
            ->setDefaults(array(
                'field' => null,
                'auto_alias' => true,
                'clause' => 'where'
            ))
            ->setAllowedValues('clause', array('where', 'having'))
            ->setAllowedTypes('field', array('string', 'null'))
            ->setAllowedTypes('auto_alias', 'bool')
            ->setNormalizer('field', function($options, $value) use ($field) {
                if (!isset($value) && $field->getName()) {
                    return $field->getName();
                } else {
                    return $value;
                }
            })
            ->setNormalizer('clause', function($options, $value) {
                return strtolower($value);
            }
            );
        ;
    }

    /**
     * Constructs proper field name from field mapping or (if absent) from own name.
     * Optionally adds alias (if missing and auto_alias option is set to true).
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

    public function getDBALType()
    {
        return null;
    }
}
