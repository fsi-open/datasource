<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Lukasz Cybula <lukasz@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Collection;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\Common\Collections\Criteria;
use FSi\Component\DataSource\Field\FieldAbstractType;

/**
 * {@inheritdoc}
 */
abstract class CollectionAbstractField extends FieldAbstractType implements CollectionFieldInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadOptionsConstraints(OptionsResolverInterface $optionsResolver)
    {
        $optionsResolver
            ->setOptional(array('field'))
            ->setAllowedTypes(array('field' => 'string'))
        ;
    }

    public function buildCriteria(Criteria $c)
    {
        $data = $this->getCleanParameter();

        if (empty($data)) {
            return;
        }

        $field = $this->hasOption('field')?$this->getOption('field'):$this->getName();
        $comparison = $this->getComparison();
        $eb = Criteria::expr();

        if ($comparison == 'between') {
            if (!is_array($data)) {
                throw new CollectionDriverException('Given data must be an array.');
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
                $c->andWhere($eb->andX($eb->lte($field, $to), $eb->gte($field, $from)));
                return;
            }
        }

        switch ($comparison) {
            case 'in':
            case 'nin':
                $data = (array) $data;
            case 'eq':
            case 'neq':
            case 'lt':
            case 'lte':
            case 'gt':
            case 'gte':
                $c->andWhere($eb->$comparison($field, $data));
                break;

            default:
                throw new CollectionDriverException(sprintf('Unexpected comparison type ("%s").', $comparison));
        }
    }
}
