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
use FSi\Component\DataSource\DataSourceInterface;

/**
 * Entity field.
 */
class Entity extends DoctrineAbstractField
{
    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $meta;

    /**
     * {@inheritdoc}
     */
    protected $comparisons = array('eq', 'memberof');

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'entity';
    }

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

        $em = $qb->getEntityManager();
        $meta = $em->getClassMetadata(get_class($data));
        $this->meta = $meta;

        switch ($comparison) {
            case 'eq':
                $qb->andWhere($qb->expr()->eq($fieldName, ":$name"));
                $qb->setParameter($name, $data);
                break;

            case 'memberof':
                $qb->andWhere(":$name MEMBER OF $fieldName");
                $qb->setParameter($name, $data);
                break;

            default:
                throw new DoctrineDriverException(sprintf('Unexpected comparison type ("%s").', $comparison));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter(&$parameters)
    {
        $datasourceName = $this->getDataSource() ? $this->getDataSource()->getName() : null;
        if (empty($datasourceName)) {
            return;
        }

        //If there's no meta, that means no parameter was assigned, so there is nothing we need to do.
        if (!isset($this->meta)) {
            return;
        }

        //Composite keys are not supported.
        if ($this->meta->isIdentifierComposite) {
            return;
        }

        $id = $this->meta->getSingleIdentifierFieldName();
        $method = 'get'.ucfirst($id);
        $parameter = $this->getCleanParameter()->$method();

        $parameter = array(
            $datasourceName => array(
                DataSourceInterface::FIELDS => array(
                    $this->getName() => $parameter,
                ),
            ),
        );

        foreach ($this->extensions as $extension) {
            $extension->preGetParameter($this, $parameter);
        }

        foreach ($this->extensions as $extension) {
            $extension->postGetParameter($this, $parameter);
        }

        $parameters = array_merge_recursive($parameters, $parameter);
    }
}