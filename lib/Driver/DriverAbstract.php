<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver;

use Countable;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DriverEvent;
use FSi\Component\DataSource\Event\DriverEvents;
use FSi\Component\DataSource\Exception\DataSourceException;
use IteratorAggregate;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * {@inheritdoc}
 */
abstract class DriverAbstract implements DriverInterface
{
    /**
     * @var DataSourceInterface
     */
    protected $datasource;

    /**
     * Extensions.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * Field types.
     *
     * @var array
     */
    protected $fieldTypes = [];

    /**
     * Fields extensions.
     *
     * @var array
     */
    protected $fieldExtensions = [];

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param array<DriverExtensionInterface> $extensions array with extensions
     * @throws DataSourceException
     */
    public function __construct(array $extensions = [])
    {
        foreach ($extensions as $extension) {
            if (!$extension instanceof DriverExtensionInterface) {
                throw new DataSourceException(sprintf(
                    'Instance of %s expected, "%s" given.',
                    DriverExtensionInterface::class,
                    get_class($extension)
                ));
            }
            $this->addExtension($extension);
        }
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
        $field->initOptions();

        if (isset($this->fieldExtensions[$type])) {
            $field->setExtensions($this->fieldExtensions[$type]);
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

        $ext = [];
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
        if (false === in_array($this->getType(), $extension->getExtendedDriverTypes())) {
            throw new DataSourceException(sprintf(
                'DataSource driver extension of class %s does not support %s driver',
                get_class($extension),
                $this->getType()
            ));
        }

        if (true === in_array($extension, $this->extensions, true)) {
            return;
        }

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
        if (!isset($this->eventDispatcher)) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     * Initialize building results i.e. prepare DQL query or initial XPath expression object.
     */
    abstract protected function initResult();

    /**
     * Build result that will be returned by getResult.
     *
     * @param array $fields
     * @param int $first
     * @param int $max
     * @return Countable&IteratorAggregate
     */
    abstract protected function buildResult($fields, $first, $max);

    public function getResult($fields, $first, $max)
    {
        $this->initResult();

        //preGetResult event.
        $event = new DriverEvent\DriverEventArgs($this, $fields);
        $this->getEventDispatcher()->dispatch(DriverEvents::PRE_GET_RESULT, $event);

        $result = $this->buildResult($fields, $first, $max);

        //postGetResult event.
        $event = new DriverEvent\ResultEventArgs($this, $fields, $result);
        $this->getEventDispatcher()->dispatch(DriverEvents::POST_GET_RESULT, $event);
        $result = $event->getResult();

        return $result;
    }
}
