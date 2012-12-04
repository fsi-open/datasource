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

/**
 * View of field, responsible for keeping some options needed during view rendering.
 */
interface FieldViewInterface
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

    /**
     * Checks whether field has option with given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasOption($name);

    /**
     * Sets option.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setOption($name, $value);

    /**
     * Returns option.
     *
     * @param string $name
     */
    public function getOption($name);

    /**
     * Returns array of options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Removes option if exists.
     *
     * @param string $name
     */
    public function removeOption($name);
}