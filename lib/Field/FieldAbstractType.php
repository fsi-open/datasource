<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Exception\FieldException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class FieldAbstractType implements FieldTypeInterface
{
    /**
     * Array of allowed comparisons.
     *
     * @var array
     */
    protected $comparisons = [];

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
    private $options = [];

    /**
     * Given parameter.
     *
     * @var mixed
     */
    protected $parameter;

    /**
     * Flag to determine if inner state has changed.
     *
     * @var bool
     */
    protected $dirty = true;

    /**
     * @var DataSourceInterface
     */
    protected $datasource;

    /**
     * @var EventDispatcher|null
     */
    private $eventDispatcher;

    /**
     * @var OptionsResolver|null
     */
    private $optionsResolver;

    /**
     * @var array
     */
    private $extensions = [];

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function __clone()
    {
        $this->eventDispatcher = null;
        $this->optionsResolver = null;
    }

    /**
     * @throws FieldException
     */
    public function setComparison($comparison)
    {
        if (false === in_array($comparison, $this->getAvailableComparisons())) {
            throw new FieldException(sprintf(
                'Comparison "%s" not allowed for this type of field ("%s").',
                $comparison,
                $this->getType()
            ));
        }

        $this->comparison = $comparison;
    }

    public function getComparison()
    {
        return $this->comparison;
    }

    public function getAvailableComparisons()
    {
        return $this->comparisons;
    }

    public function setOptions($options)
    {
        $this->options = $this->getOptionsResolver()->resolve($options);
    }

    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    /**
     * @throws FieldException
     */
    public function getOption($name)
    {
        if (false === $this->hasOption($name)) {
            throw new FieldException(sprintf(
                'There\'s no option named "%s"',
                is_scalar($name) ? $name : gettype($name)
            ));
        }

        return $this->options[$name];
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function bindParameter($parameter)
    {
        $this->setDirty();

        //PreBindParameter event.
        $event = new FieldEvent\ParameterEventArgs($this, $parameter);
        $this->getEventDispatcher()->dispatch(FieldEvents::PRE_BIND_PARAMETER, $event);
        $parameter = $event->getParameter();

        $datasourceName = $this->getDataSource() ? $this->getDataSource()->getName() : null;
        if (
            !empty($datasourceName)
            && isset($parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$this->getName()])
        ) {
            $parameter = $parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$this->getName()];
        } else {
            $parameter = null;
        }

        $this->parameter = $parameter;

        //PreBindParameter event.
        $event = new FieldEvent\FieldEventArgs($this);
        $this->getEventDispatcher()->dispatch(FieldEvents::POST_BIND_PARAMETER, $event);
    }

    public function getParameter(&$parameters)
    {
        $datasourceName = $this->getDataSource() ? $this->getDataSource()->getName() : null;
        if (!empty($datasourceName)) {
            $parameter = [
                $datasourceName => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        $this->getName() => $this->getCleanParameter(),
                    ],
                ],
            ];
        } else {
            $parameter = [];
        }

        //PostGetParameter event.
        $event = new FieldEvent\ParameterEventArgs($this, $parameter);
        $this->getEventDispatcher()->dispatch(FieldEvents::POST_GET_PARAMETER, $event);
        $parameter = $event->getParameter();

        $parameters = array_merge_recursive($parameters, $parameter);
    }

    public function getCleanParameter()
    {
        return $this->parameter;
    }

    public function addExtension(FieldExtensionInterface $extension)
    {
        if (in_array($extension, $this->extensions, true)) {
            return;
        }

        $this->getEventDispatcher()->addSubscriber($extension);
        $extension->initOptions($this);
        $this->extensions[] = $extension;

        $this->options = $this->getOptionsResolver()->resolve($this->options);
    }

    public function setExtensions(array $extensions)
    {
        foreach ($extensions as $extension) {
            if (false === $extension instanceof FieldExtensionInterface) {
                throw new FieldException(sprintf(
                    'Expected instance of %s, %s given',
                    FieldExtensionInterface::class,
                    get_class($extension)
                ));
            }

            $this->getEventDispatcher()->addSubscriber($extension);
            $extension->initOptions($this);
        }
        $this->options = $this->getOptionsResolver()->resolve($this->options);
        $this->extensions = $extensions;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function createView()
    {
        $view = new FieldView($this);

        //PostBuildView event.
        $event = new FieldEvent\ViewEventArgs($this, $view);
        $this->getEventDispatcher()->dispatch(FieldEvents::POST_BUILD_VIEW, $event);

        return $view;
    }

    public function isDirty()
    {
        return $this->dirty;
    }

    public function setDirty($dirty = true)
    {
        $this->dirty = (bool) $dirty;
    }

    public function setDataSource(DataSourceInterface $datasource)
    {
        $this->datasource = $datasource;
    }

    public function getDataSource()
    {
        return $this->datasource;
    }

    public function initOptions()
    {
    }

    public function getOptionsResolver()
    {
        if (!isset($this->optionsResolver)) {
            $this->optionsResolver = new OptionsResolver();
        }

        return $this->optionsResolver;
    }

    protected function getEventDispatcher()
    {
        if (!isset($this->eventDispatcher)) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }
}
