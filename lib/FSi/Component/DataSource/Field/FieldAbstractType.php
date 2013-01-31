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

use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Exception\FieldException;
use FSi\Component\DataSource\DataSourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Event\FieldEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
    private $options = array();

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
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /*
     * @var array
     */
    private $extensions;

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
     * Constructor.
     */
    public function __construct()
    {
        $this->eventDispatcher = new EventDispatcher();
        $this->optionsResolver = new OptionsResolver();
        $this->extensions = array();
        $this->loadOptionsConstraints($this->optionsResolver);
        $this->options = $this->optionsResolver->resolve(array());
    }

    /**
     * Cloning.
     */
    public function __clone()
    {
        $this->eventDispatcher = new EventDispatcher();
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
        $this->options = $this->optionsResolver->resolve($options);
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

        //PreBindParameter event.
        $event = new FieldEvent\ParameterEventArgs($this, $parameter);
        $this->eventDispatcher->dispatch(FieldEvents::PRE_BIND_PARAMETER, $event);
        $parameter = $event->getParameter();

        $datasourceName = $this->getDataSource() ? $this->getDataSource()->getName() : null;
        if (!empty($datasourceName) && isset($parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$this->getName()])) {
            $parameter = $parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$this->getName()];
        } else {
            $parameter = null;
        }

        $this->parameter = $parameter;

        //PreBindParameter event.
        $event = new FieldEvent\FieldEventArgs($this);
        $this->eventDispatcher->dispatch(FieldEvents::POST_BIND_PARAMETER, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter(&$parameters)
    {
        $datasourceName = $this->getDataSource() ? $this->getDataSource()->getName() : null;
        if (!empty($datasourceName)) {
            $parameter = array(
                $datasourceName => array(
                    DataSourceInterface::PARAMETER_FIELDS => array(
                        $this->getName() => $this->getCleanParameter(),
                    ),
                ),
            );
        } else {
            $parameter = array();
        }

        //PreGetParameter event.
        $event = new FieldEvent\ParameterEventArgs($this, $parameter);
        $this->eventDispatcher->dispatch(FieldEvents::PRE_GET_PARAMETER, $event);
        $parameter = $event->getParameter();

        //PostGetParameter event.
        $event = new FieldEvent\ParameterEventArgs($this, $parameter);
        $this->eventDispatcher->dispatch(FieldEvents::POST_GET_PARAMETER, $event);
        $parameter = $event->getParameter();

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
        if (in_array($extension, $this->extensions, true)) {
            return;
        }

        $this->eventDispatcher->addSubscriber($extension);
        $extension->loadOptionsConstraints($this->optionsResolver);
        $this->extensions[] = $extension;

        $this->options = $this->optionsResolver->resolve($this->options);
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
    public function createView()
    {
        $view = new FieldView($this);

        //PreBuildView event.
        $event = new FieldEvent\ViewEventArgs($this, $view);
        $this->eventDispatcher->dispatch(FieldEvents::PRE_BUILD_VIEW, $event);

        //PostBuildView event.
        $event = new FieldEvent\ViewEventArgs($this, $view);
        $this->eventDispatcher->dispatch(FieldEvents::POST_BUILD_VIEW, $event);

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
    public function loadOptionsConstraints(OptionsResolverInterface $optionsResolver)
    {

    }
}
