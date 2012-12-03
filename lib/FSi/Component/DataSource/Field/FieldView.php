<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Exception\FieldViewException;

/**
 * {@inheritdoc}
 */
class FieldView implements FieldViewInterface
{
    /**
     * @var FieldTypeInterface
     */
    private $field;

    /**
     * @var DataSourceViewInterface
     */
    private $dataSourceView;

    /**
     * Options.
     *
     * @var array
     */
    private $options = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(FieldTypeInterface $field)
    {
        $this->field = $field;

    }

    /**
     * {@inheritdoc}
     */
    public function getField()
    {
        return $this->field;
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

    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        //Case when i.e. null was given as $value is problematic,
        //because then you can getOption with that name, but hasOption will return false,
        //also that key would appear in array from getOptions method.
        if (!isset($this->options[$name])) {
            unset($this->options[$name]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws DataSourceViewException
     */
    public function getOption($name)
    {
        if (!$this->hasOption($name)) {
            throw new FieldViewException(sprintf('There\'s no option with name "%s"', $name));
        }
        return $this->options[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($name)
    {
        if (isset($this->options[$name])) {
            unset($this->options[$name]);
        }
    }
}