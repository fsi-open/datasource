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

use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Exception\FieldException;
use FSi\Component\DataSource\DataSourceInterface;

/**
 * {@inheritdoc}
 */
abstract class FieldAbstractType implements FieldTypeInterface
{
    /**
     * Array of allowed comparisons.
     *
     * @var array
     */
    protected $comparisons = array();

    /**
     * Name of element.
     *
     * @var string
     */
    protected $name;

    /**
     * Set comparison.
     *
     * @var string
     */
    protected $comparison;

    /**
     * Given options.
     *
     * @var array
     */
    protected $options = array();

    /**
     * Given parameter.
     *
     * @var mixed
     */
    protected $parameter;

    /**
     * Array of loaded extensions.
     *
     * @var array
     */
    protected $extensions = array();

    /**
     * Flag to determine if inner state has changed.
     * Enter description here ...
     * @var unknown_type
     */
    protected $dirty = true;

    /**
     * @var DataSourceInterface
     */
    protected $datasource;

    /**
     * Flag to determine if from last check any new extension was added or not.
     *
     * @var bool
     */
    private $extensionsDirty = true;

    /**
     * Cache for available options keys.
     *
     * @var array
     */
    private $availableOptions;

    /**
     * Cache for required options keys.
     *
     * @var array
     */
    private $requiredOptions;

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
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
    public function setComparison($comparison)
    {
        if (!in_array($comparison, $this->getAvailableComparisons())) {
            throw new FieldException(sprintf('Comparison "%s" not allowed for this type of field ("%s").', $comparison, $this->getType()));
        }

        $this->comparison = $comparison;
    }

    /**
     * {@inheritdoc}
     */
    public function getComparison()
    {
        return $this->comparison;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableComparisons()
    {
        return $this->comparisons;
    }

    /**
     * {@inheritdoc}
     *
     * @throws FieldException
     */
    public function setOptions($options)
    {
        if ($this->options === $options) {
            return;
        }

        $this->setDirty();
        $options = array_merge($this->getCachedDefaultAvailableOptions(), $this->getCachedDefaultRequiredOptions(), (array) $options);

        $this->checkExtensionsClarity();

        $available = $this->getCachedAvailableOptions();
        $required = $this->getCachedRequiredOptions();
        $keys = array_keys($options);

        if ($rkeys = array_diff($required, $keys)) {
            throw new FieldException(sprintf('Missing some required fields (%s).', implode(', ', $rkeys)));
        }

        if ($akeys = array_diff($keys, $available)) {
            throw new FieldException(sprintf('Some of given options (%s) are not available for this field.', implode(', ', $akeys)));
        }

        $this->options = $options;
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
     *
     * @throws FieldException
     */
    public function getOption($name)
    {
        if (!$this->hasOption($name)) {
            throw new FieldException(sprintf('There\'s no option named "%s"', is_scalar($name) ? $name : gettype($name)));
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
    public function bindParameter($parameter)
    {
        $this->setDirty();

        foreach ($this->extensions as $extension) {
            $extension->preBindParameter($this, $parameter);
        }

        $datasourceName = $this->getDataSource() ? $this->getDataSource()->getName() : null;
        if (
            !empty($datasourceName)
            && is_array($parameter)
            && isset($parameter[$datasourceName])
            && isset($parameter[$datasourceName][DataSourceInterface::FIELDS])
            && isset($parameter[$datasourceName][DataSourceInterface::FIELDS][$this->getName()])
        ) {
            $parameter = $parameter[$datasourceName][DataSourceInterface::FIELDS][$this->getName()];
        } else {
            $parameter = null;
        }

        $this->parameter = $parameter;

        foreach ($this->extensions as $extension) {
            $extension->postBindParameter($this);
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

        $parameter = array(
            $datasourceName => array(
                DataSourceInterface::FIELDS => array(
                    $this->getName() => $this->getCleanParameter(),
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

    /**
     * {@inheritdoc}
     */
    public function getCleanParameter()
    {
        return $this->parameter;
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(FieldExtensionInterface $extension)
    {
        $this->extensionsDirty = true;
        $this->extensions[] = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function createView()
    {
        $view = new FieldView($this);

        foreach ($this->extensions as $extension) {
            $extension->preBuildView($this, $view);
        }

        foreach ($this->extensions as $extension) {
            $extension->postBuildView($this, $view);
        }

        return $view;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirty()
    {
        return $this->dirty;
    }

    /**
     * {@inheritdoc}
     */
    public function setDirty($dirty = true)
    {
        $this->dirty = (bool) $dirty;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataSource(DataSourceInterface $datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataSource()
    {
        return $this->datasource;
    }

    /**
     * {@inheritdoc}
     */
    public function removeDataSource()
    {
        unset($this->datasource);
    }

    /**
     * Checks if any extension was added recently, and if did, clears options cache.
     */
    private function checkExtensionsClarity()
    {
        if ($this->extensionsDirty) {
            unset($this->availableOptions);
            unset($this->requiredOptions);
            unset($this->defaultAvailableOptions);
            unset($this->defaultRequiredOptions);
        }
        $this->extensionsDirty = false;
    }

    /**
     * Returns available options keys names.
     *
     * @return array
     */
    private function getCachedAvailableOptions()
    {
        if (!isset($this->availableOptions)) {
            $available = $this->getAvailableOptions();

            foreach ($this->extensions as $extension) {
                $available = array_merge($available, (array) $extension->getAvailableOptions());
            }

            //Available options need to have required keys too.
            $this->availableOptions = array_merge($available, $this->getCachedRequiredOptions());
        }
        return $this->availableOptions;
    }

    /**
     * Return required options keys names.
     *
     * @return array
     */
    private function getCachedRequiredOptions()
    {
        if (!isset($this->requiredOptions)) {
            $required = $this->getRequiredOptions();

            foreach ($this->extensions as $extension) {
                $required = array_merge($required, (array) $extension->getRequiredOptions());
            }
            $this->requiredOptions = $required;
        }

        return $this->requiredOptions;
    }

    /**
     * Returns array of default available options.
     *
     * @return array
     */
    private function getCachedDefaultAvailableOptions()
    {
        if (!isset($this->defaultAvailableOptions)) {
            $options = $this->getDefaultAvailableOptions();

            foreach ($this->extensions as $extension) {
                $options = array_merge($options, (array) $extension->getDefaultAvailableOptions());
            }

            $this->defaultAvailableOptions = $options;
        }

        return $this->defaultAvailableOptions;
    }

    /**
     * Returns array of default required options.
     *
     * @return array
     */
    private function getCachedDefaultRequiredOptions()
    {
        if (!isset($this->defaultRequiredOptions)) {
            $options = $this->getDefaultRequiredOptions();

            foreach ($this->extensions as $extension) {
                $options = array_merge($options, (array) $extension->getDefaultRequiredOptions());
            }

            $this->defaultRequiredOptions = $options;
        }

        return $this->defaultRequiredOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultAvailableOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultRequiredOptions()
    {
        return array();
    }
}