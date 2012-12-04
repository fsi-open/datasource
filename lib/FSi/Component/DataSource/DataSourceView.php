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

use FSi\Component\DataSource\Exception\DataSourceViewException;

/**
 * {@inheritdoc}
 */
class DataSourceView implements DataSourceViewInterface
{
    /**
     * @var DataSource
     */
    private $datasource;

    /**
     * Array of field views.
     *
     * @var array
     */
    private $fields = array();

    /**
     * Options of view.
     *
     * @var array
     */
    private $options = array();

    /**
     * Constructor.
     *
     * @param DataSource $datasource
     */
    public function __construct(DataSource $datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * {@inheritdoc}
     */
    public function countPages()
    {
        $maxresults = $this->datasource->getMaxResults();
        if ($maxresults == 0) {
            return 1;
        } else {
            return ceil(count($this->getResult())/$maxresults);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage()
    {
        $maxresults = $this->datasource->getMaxResults();
        if ($maxresults == 0) {
            return 1;
        } else {
            $current = $this->datasource->getFirstResult();
            return floor($current/$maxresults) + 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->datasource->getParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllParameters()
    {
        return $this->datasource->getAllParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function getOtherParameters()
    {
        return $this->datasource->getOtherParameters();
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
            throw new DataSourceViewException(sprintf('There\'s no option with name "%s"', $name));
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

    /**
     * {@inheritdoc}
     */
    public function hasField($name)
    {
        return isset($this->fields[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getField($name)
    {
        if (!$this->hasField($name)) {
            throw new DataSourceViewException(sprintf('There\'s no field with name "%s"', $name));
        }
        return $this->fields[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function addField(Field\FieldViewInterface $fieldView)
    {
        $name = $fieldView->getField()->getName();
        if ($this->hasField($name)) {
            throw new DataSourceViewException(sprintf('There\'s already field with name "%s"', $name));
        }
        $this->fields[$name] = $fieldView;
        $fieldView->setDataSourceView($this);
    }

    /**
     * Method to fetch result from datasource.
     *
     * @return mixed
     */
    private function getResult()
    {
        return $this->datasource->getResult();
    }
}