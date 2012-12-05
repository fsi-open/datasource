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
use FSi\Component\DataSource\Util\AttributesContainerInterface;

/**
 * View of field, responsible for keeping some options needed during view rendering.
 */
interface FieldViewInterface extends AttributesContainerInterface
{
    /**
     * Constructor.
     *
     * @param FieldTypeInterface $field
     */
    public function __construct(FieldTypeInterface $field);

    /**
     * Return assigned field.
     *
     * @return FieldTypeInterface
     */
    public function getField();

    /**
     * Sets DataSource view.
     *
     * @param DataSourceViewInterface $dataSourceView
     */
    public function setDataSourceView(DataSourceViewInterface $dataSourceView);

    /**
     * Return assigned DataSource view.
     *
     * @return DataSourceViewInterface
     */
    public function getDataSourceView();
}