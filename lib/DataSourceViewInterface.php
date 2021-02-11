<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource;

use Countable;
use Doctrine\Common\Collections\ArrayCollection;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Util\AttributesContainerInterface;
use IteratorAggregate;

/**
 * DataSources view is responsible for keeping options needed to build view, fields view objects,
 * and proxy some requests to DataSource.
 */
interface DataSourceViewInterface extends AttributesContainerInterface, \ArrayAccess, \Countable, \SeekableIterator
{
    /**
     * Returns name of datasource.
     *
     * @return string
     */
    public function getName();

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
     * Checks whether view has field with given name.
     *
     * @param string $name
     */
    public function hasField($name);

    /**
     * Removes field with given name.
     *
     * @param string $name
     */
    public function removeField($name);

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
     * Removes all fields.
     *
     * @return DataSourceViewInterface
     */
    public function clearFields();

    /**
     * Adds new field view.
     *
     * @param FieldViewInterface $fieldView
     */
    public function addField(Field\FieldViewInterface $fieldView);

    /**
     * Replace fields with specified ones.
     *
     * Each of field must be instance of \FSi\Component\DataSource\Field\FieldViewInterface
     *
     * @param FieldViewInterface[] $fields
     */
    public function setFields(array $fields);

    /**
     * @return Countable&IteratorAggregate
     */
    public function getResult();
}
