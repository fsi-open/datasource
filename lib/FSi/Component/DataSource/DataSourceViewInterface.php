<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource;

/**
 * DataSources view is responsible for keeping options needed to build view, fields view objects,
 * and proxy some requests to DataSource.
 */
interface DataSourceViewInterface
{
    /**
     * Counts all available pages.
     *
     * @return int
     */
    public function countPages();

    /**
     * Returns number of current page.
     *
     * @return int
     */
    public function getCurrentPage();

    /**
     * Returns parameters that were binded to datasource.
     *
     * @return array
     */
    public function getParameters();

    /**
     * Returns parameters that were binded to all datasources.
     *
     * @return array
     */
    public function getAllParameters();

    /**
     * Returns parameters that were binded to other datasources.
     *
     * @return array
     */
    public function getOtherParameters();

    /**
     * Checks whether view has option with $name.
     *
     * @param string $name
     * @return bool
     */
    public function hasOption($name);

    /**
     * Sets option $name with $value.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setOption($name, $value);

    /**
     * Get option for $name.
     *
     * @param string $name
     * @return mixed
     */
    public function getOption($name);

    /**
     * Return array of options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Removes given options.
     *
     * @param string $name
     */
    public function removeOption($name);

    /**
     * Checks whether view has field with given name.
     *
     * @param string $name
     */
    public function hasField($name);

    /**
     * Returns field with given name.
     *
     * @param string $name
     */
    public function getField($name);

    /**
     * Return array of all fields.
     *
     * @return array
     */
    public function getFields();

    /**
     * Adds new field view.
     *
     * @param Field\FieldViewInterface $fieldView
     */
    public function addField(Field\FieldViewInterface $fieldView);
}