<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Exception\FieldViewException;
use FSi\Component\DataSource\Util\AttributesContainer;

/**
 * {@inheritdoc}
 */
class FieldView extends AttributesContainer implements FieldViewInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var DataSourceViewInterface
     */
    private $dataSourceView;

    /**
     * {@inheritdoc}
     */
    public function __construct(FieldTypeInterface $field)
    {
        $this->name = $field->getName();
        $this->type = $field->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataSourceView(DataSourceViewInterface $dataSourceView)
    {
        $this->dataSourceView = $dataSourceView;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataSourceView()
    {
        return $this->dataSourceView;
    }
}