<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Collection;

use Doctrine\Common\Collections\Criteria;
use FSi\Component\DataSource\Driver\Collection\Exception\CollectionDriverException;
use FSi\Component\DataSource\Field\FieldAbstractType;

use function count;

abstract class CollectionAbstractField extends FieldAbstractType implements CollectionFieldInterface
{
    public function initOptions()
    {
        $field = $this;
        $this->getOptionsResolver()
            ->setDefined(['field'])
            ->setAllowedTypes('field', ['string', 'null'])
            ->setNormalizer('field', function ($options, $value) use ($field) {
                if (!isset($value) && $field->getName()) {
                    return $field->getName();
                } else {
                    return $value;
                }
            });
        ;
    }

    public function buildCriteria(Criteria $c)
    {
        $data = $this->getCleanParameter();

        if (($data === []) || ($data === '') || ($data === null)) {
            return;
        }

        $type = $this->getPHPType();
        $field = $this->hasOption('field') ? $this->getOption('field') : $this->getName();
        $comparison = $this->getComparison();
        $eb = Criteria::expr();

        if ('between' === $comparison) {
            if (false === is_array($data)) {
                throw new CollectionDriverException(
                    'Fields with \'between\' comparison require to bind an array.'
                );
            }

            $from = array_shift($data);
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
                if (isset($type)) {
                    settype($from, $type);
                    settype($to, $type);
                }
                $c->andWhere($eb->andX($eb->lte($field, $to), $eb->gte($field, $from)));
                return;
            }
        }

        if ($comparison === 'nin') {
            $comparison = 'notIn';
        }

        if (in_array($comparison, ['in', 'nin', 'notIn']) && !is_array($data)) {
            throw new CollectionDriverException(
                'Fields with \'in\' and \'notIn\' comparisons require to bind an array.'
            );
        }

        if (isset($type)) {
            settype($data, $type);
        }
        $c->andWhere($eb->$comparison($field, $data));
    }

    public function getPHPType()
    {
        return null;
    }
}
