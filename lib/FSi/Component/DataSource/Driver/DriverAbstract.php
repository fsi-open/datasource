<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver;

use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\DataSourceInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use FSi\Component\DataSource\Event\DriverEvents;
use FSi\Component\DataSource\Event\DriverEvent;

/**
 * {@inheritdoc}
 */
abstract class DriverAbstract implements DriverInterface
{
    /**
     * Extensions.
     *
     * @var array
     */
    protected $extensions = array();

    /**
     * Field types.
     *
     * @var array
     */
    protected $fieldTypes = array();

    /**
     * Fields extensions.
     *
     * @var array
     */
    protected $fieldExtensions = array();

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Constructor.
     *
     * @throws DataSourceException
     * @param $extensions array with extensions
     */
    public function __construct($extensions = array())
    {
        if (!is_array($extensions)) {
            throw new DataSourceException(sprintf('Array of extensions expected, "%s" given.', gettype($extensions)));
        }

        foreach ($extensions as $extension) {
            if (!($extension instanceof DriverExtensionInterface)) {
                throw new DataSourceException(sprintf('Instance of DriverExtensionInterface expected, "%s" given.', get_class($extension)));
            }
            $this->addExtension($extension);
        }

        $this->eventDispatcher = new EventDispatcher();
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
    public function hasFieldType($type)
    {
        $this->initFieldType($type);
        return isset($this->fieldTypes[$type]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType($type)
    {
        if (!$this->hasFieldType($type)) {
            throw new DataSourceException(sprintf('Unsupported field type ("%s").', $type));
        }

        $field = clone $this->fieldTypes[$type];

        if (isset($this->fieldExtensions[$type])) {
            foreach ($this->fieldExtensions[$type] as $fieldExtension) {
                $field->addExtension($fieldExtension);
            }
        }

        return $field;
    }

    /**
     * Inits field for given type (including extending that type) and saves it as pattern for later cloning.
     *
     * @param string $type
     */
    private function initFieldType($type)
    {
        if (isset($this->fieldTypes[$type])) {
            return;
        }

        $typeInstance = false;
        foreach ($this->extensions as $extension) {
            if ($extension->hasFieldType($type)) {
                $typeInstance = $extension->getFieldType($type);
                break;
            }
        }

        if (!$typeInstance) {
            return;
        }

        $this->fieldTypes[$type] = $typeInstance;

        $ext = array();
        foreach ($this->extensions as $extension) {
            if ($extension->hasFieldTypeExtensions($type)) {
                $fieldExtensions = $extension->getFieldTypeExtensions($type);
                foreach ($fieldExtensions as $fieldExtension) {
                    $ext[] = $fieldExtension;
                }
            }
        }

        $this->fieldExtensions[$type] = $ext;
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
    public function addExtension(DriverExtensionInterface $extension)
    {
        if (!in_array($this->getType(), $extension->getExtendedDriverTypes()))
            throw new DataSourceException(sprintf('DataSource driver extension of class %s does not support %s driver', get_class($extension), $this->getType()));

        $eventDispatcher = $this->getEventDispatcher();
        foreach ($extension->loadSubscribers() as $subscriber) {
            $eventDispatcher->addSubscriber($subscriber);
        }

        $this->extensions[] = $extension;
    }

    /**
     * Returns reference to EventDispatcher.
     *
     * @return EventDispatcher
     */
    protected function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Initialize building results i.e. prepare DQL query or initial XPath expression object
     */
    abstract protected function initResult();

    /**
     * Build result that will be returned by getResult
     *
     * @param array $fields
     * @param int $first
     * @param int $max
     * @return Countable, IteratorAggregate
     */
    abstract protected function buildResult($fields, $first, $max);

    /**
     * {@inheritdoc}
     */
    public function getResult($fields, $first, $max)
    {
        $this->initResult();

        //preGetResult event.
        $event = new DriverEvent\DriverEventArgs($this);
        $this->eventDispatcher->dispatch(DriverEvents::PRE_GET_RESULT, $event);

        $result = $this->buildResult($fields, $first, $max);

        //postGetResult event.
        $event = new DriverEvent\ResultEventArgs($this, $result);
        $this->eventDispatcher->dispatch(DriverEvents::POST_GET_RESULT, $event);
        $result = $event->getResult();

        return $result;
    }
}
