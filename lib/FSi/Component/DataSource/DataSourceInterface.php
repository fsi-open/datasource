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
 * DataSource abstracts fetching data from various sources. For more information about usage please view README file.
 *
 * DataSource maintains communication with driver, manipulating fields (adding, removing, etc.), calling DataSource extensions events,
 * view creation and more. It's first and main interface client will communicate with.
 */
interface DataSourceInterface
{
    /**
     * Key for fields data.
     */
    const FIELDS = 'fields';

    /**
     * Key for page info.
     */
    const PAGE = 'page';

    /**
     * Returns name of the DataSource.
     *
     * @return string
     */
    public function getName();

    /**
     * Checks whether data source has field with given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasField($name);

    /**
     * Adds field to data source.
     *
     * Keep in mind, that this method should be able to add field object, if such given as first argument. If so,
     * $type and $comparison are mandatory and it's up to implementation to check whether are given.
     *
     * @param object|string $name
     * @param string $type
     * @param string $comparison
     * @param array $options
     */
    public function addField($name, $type = null, $comparison = null, $options = array());

    /**
     * Removes given field.
     *
     * If there wasn't field with given name, method will return false.
     *
     * @param string $name
     * @return bool
     */
    public function removeField($name);

    /**
     * Returns field for given name.
     *
     * @param string $name
     */
    public function getField($name);

    /**
     * Returns array of all fields.
     *
     * @return array
     */
    public function getFields();

    /**
     * Removes all fields from datasource.
     */
    public function clearFields();

    /**
     * Sets maximal amount of result that will be returned.
     *
     * It should just proxy request to driver.
     * If 0, then theres no limit.
     *
     * @param int $max
     */
    public function setMaxResults($max);

    /**
     * Sets number of result (in general), that will be first in collection of returned results.
     *
     * It should just proxy request to driver.
     *
     * @param int $first
     */
    public function setFirstResult($first);

    /**
     * Return maximal amount of results.
     *
     * It should just proxy request to driver.
     *
     * @return int
     */
    public function getMaxResults();

    /**
     * Returns first result offset.
     *
     * It should just proxy request to driver.
     *
     * @return int
     */
    public function getFirstResult();

    /**
     * Binds parameters to fields.
     *
     * @param array $parameters
     */
    public function bindParameters($parameters = array());

    /**
     * Returns collection with result.
     *
     * It should just proxy request to driver.
     *
     * @return Countable, IteratorAggregate
     */
    public function getResult();

    /**
     * Adds extension.
     *
     * @param DataSourceExtensionInterface $extension
     */
    public function addExtension(DataSourceExtensionInterface $extension);

    /**
     * Return array of loaded extensions.
     *
     * @return array
     */
    public function getExtensions();

    /**
     * Return ready view.
     *
     * @return DataSourceViewInterface
     */
    public function createView();

    /**
     * Returns parameters of returned data.
     *
     * @return array
     */
    public function getParameters();

    /**
     * Returns all parameters from all datasources on page.
     *
     * Works properly only if factory is assigned, or just created through factory,
     * and all others datasources were created through that factory. Otherwise (if
     * no factory assigned, or if it's the only one datasource that far) it will
     * return the same result as getParameters method.
     *
     * @return array
     */
    public function getAllParameters();

    /**
     * Returns all parameters from all datasources on page except this one.
     *
     * Constraints similars to these of getAllParameters method - if no factory
     * assigned, method will return empty array.
     *
     * @return array
     */
    public function getOtherParameters();

    /**
     * Sets factory reference to DataSource.
     *
     * DataSource needs that reference for example during getAllParameters method.
     *
     * @param DataSourceFactoryInterface $factory
     */
    public function setFactory(DataSourceFactoryInterface $factory);

    /**
     * Return assigned factory.
     *
     * @return DataSourceFactoryInterface|null
     */
    public function getFactory();
}
