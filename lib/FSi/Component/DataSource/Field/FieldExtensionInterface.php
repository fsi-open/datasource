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

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;

/**
 * Extension of DataSources field.
 */
interface FieldExtensionInterface
{
    /**
     * Returns array of available options.
     *
     * @return array
     */
    public function getAvailableOptions();

    /**
     * Returns array of default available options.
     *
     * @return array
     */
    public function getDefaultAvailableOptions();

    /**
     * Returns array of required options.
     *
     * @return array
     */
    public function getRequiredOptions();

    /**
     * Returns array of default required options.
     *
     * @return array
     */
    public function getDefaultRequiredOptions();

    /**
     * Returns array of extended types.
     *
     * @return array
     */
    public function getExtendedFieldTypes();

    /**
     * Method called before binding parameter.
     *
     * @param FieldTypeInterface $field
     * @param mixed &$data
     */
    public function preBindParameter(FieldTypeInterface $field, &$parameter);

    /**
     * Method called after binding parameter.
     *
     * @param FieldTypeInterface $field
     */
    public function postBindParameter(FieldTypeInterface $field);

    /**
     * Method called before getting parameter.
     *
     * @param FieldTypeInterface $field
     */
    public function preGetParameter(FieldTypeInterface $field, &$parameter);

    /**
     * Method called after getting parameter.
     *
     * @param FieldTypeInterface $field
     */
    public function postGetParameter(FieldTypeInterface $field, &$parameter);

    /**
     * Method called before building view.
     *
     * @param FieldTypeInterface $field
     * @param FieldViewInterface $view
     */
    public function preBuildView(FieldTypeInterface $field, FieldViewInterface $view);

    /**
     * Method called after building view.
     *
     * @param FieldTypeInterface $field
     * @param FieldViewInterface $view
     */
    public function postBuildView(FieldTypeInterface $field, FieldViewInterface $view);
}