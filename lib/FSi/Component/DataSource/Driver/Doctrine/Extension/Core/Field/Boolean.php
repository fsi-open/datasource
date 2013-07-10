<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField;
use FSi\Component\DataSource\Driver\Doctrine\Exception\DoctrineDriverException;
use Doctrine\ORM\QueryBuilder;

/**
 * Boolean field.
 */
class Boolean extends DoctrineAbstractField
{
    /**
     * {@inheritdoc}
     */
    protected $comparisons = array('eq');

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'boolean';
    }

    /**
     * {@inheritdoc}
     */
    public function buildQuery(QueryBuilder $qb, $alias)
    {
        $data = $this->getCleanParameter();
        $fieldName = $this->getFieldName($alias);
        $name = $this->getName();

        if (empty($data) && ($data !== 0 && $data !== false)) {
            return;
        }

        $comparison = $this->getComparison();
        if (!in_array($comparison, $this->comparisons)) {
            throw new DoctrineDriverException(sprintf('Unexpected comparison type ("%s").', $comparison));
        }

        $func = sprintf('and%s', ucfirst($this->getOption('clause')));

        $qb->$func($qb->expr()->$comparison($fieldName, ":$name"));
        $qb->setParameter($this->getName(), $data, \Doctrine\DBAL\Types\Type::BOOLEAN);
    }
}