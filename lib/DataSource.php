<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource;

use Countable;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use IteratorAggregate;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DataSource implements DataSourceInterface
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $extensions = [];

    /**
     * @var DataSourceView
     */
    private $view;

    /**
     * @var DataSourceFactoryInterface|null
     */
    private $factory;

    /**
     * @var int
     */
    private $maxResults;

    /**
     * @var int
     */
    private $firstResult;

    /**
     * Cache for methods that depends on fields data (cache is dropped whenever
     * any of fields is dirty, or fields have changed).
     *
     * @var array
     */
    private $cache = [];

    /**
     * Flag set as true when fields or their data is modifying, or even new
     * extension is added.
     *
     * @var bool
     */
    private $dirty = true;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param DriverInterface $driver
     * @param string $name
     * @throws DataSourceException
     */
    public function __construct(DriverInterface $driver, $name = 'datasource')
    {
        $name = (string) $name;

        if (empty($name)) {
            throw new DataSourceException('Name of data source can\t be empty.');
        }

        if (!preg_match('/^[\w\d]+$/', $name)) {
            throw new DataSourceException('Name of data source may contain only word characters and digits.');
        }

        $this->driver = $driver;
        $this->name = $name;
        $this->eventDispatcher = new EventDispatcher();
        $driver->setDataSource($this);
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
    public function addField($name, $type = null, $comparison = null, $options = [])
    {
        if ($name instanceof FieldTypeInterface) {
            $field = $name;
            $name = $name->getName();

            if (empty($name)) {
                throw new DataSourceException('Given field has no name set.');
            }
        } else {
            if (empty($type)) {
                throw new DataSourceException('"type" can\'t be null.');
            }
            if (empty($comparison)) {
                throw new DataSourceException('"comparison" can\'t be null.');
            }
            $field = $this->driver->getFieldType($type);
            $field->setName($name);
            $field->setComparison($comparison);
            $field->setOptions($options);
        }

        $this->dirty = true;
        $this->fields[$name] = $field;
        $field->setDataSource($this);

        return $this;
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
    public function removeField($name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
            $this->dirty = true;
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getField($name)
    {
        if (!$this->hasField($name)) {
            throw new DataSourceException(sprintf('There\'s no field with name "%s"', $name));
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
    public function clearFields()
    {
        $this->fields = [];
        $this->dirty = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bindParameters($parameters = [])
    {
        $this->dirty = true;

        //PreBindParameters event.
        $event = new DataSourceEvent\ParametersEventArgs($this, $parameters);
        $this->eventDispatcher->dispatch(DataSourceEvents::PRE_BIND_PARAMETERS, $event);
        $parameters = $event->getParameters();

        if (!is_array($parameters)) {
            throw new DataSourceException('Given parameters must be an array.');
        }

        foreach ($this->getFields() as $field) {
            $field->bindParameter($parameters);
        }

        //PostBindParameters event.
        $event = new DataSourceEvent\DataSourceEventArgs($this);
        $this->eventDispatcher->dispatch(DataSourceEvents::POST_BIND_PARAMETERS, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        $this->checkFieldsClarity();

        if (
            isset($this->cache['result'])
            && $this->cache['result']['maxresults'] == $this->getMaxResults()
            && $this->cache['result']['firstresult'] == $this->getFirstResult()
        ) {
            return $this->cache['result']['result'];
        }

        //PreGetResult event.
        $event = new DataSourceEvent\DataSourceEventArgs($this);
        $this->eventDispatcher->dispatch(DataSourceEvents::PRE_GET_RESULT, $event);

        $result = $this->driver->getResult($this->fields, $this->getFirstResult(), $this->getMaxResults());
        if (false === is_object($result)) {
            throw new DataSourceException(sprintf(
                'Returned result must be object implementing both %s and %s.',
                Countable::class,
                IteratorAggregate::class
            ));
        }

        if ((false === $result instanceof IteratorAggregate) || (false === $result instanceof Countable)) {
            throw new DataSourceException(sprintf(
                'Returned result must be both %s and %s, instance of "%s" given.',
                Countable::class,
                IteratorAggregate::class,
                get_class($result)
            ));
        }


        foreach ($this->getFields() as $field) {
            $field->setDirty(false);
        }

        // PostGetResult event.
        $event = new DataSourceEvent\ResultEventArgs($this, $result);
        $this->eventDispatcher->dispatch(DataSourceEvents::POST_GET_RESULT, $event);
        $result = $event->getResult();

        // Creating cache.
        $this->cache['result'] = [
            'result' => $result,
            'firstresult' => $this->getFirstResult(),
            'maxresults' => $this->getMaxResults(),
        ];

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMaxResults($max)
    {
        $this->dirty = true;
        $this->maxResults = $max;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstResult($first)
    {
        $this->dirty = true;
        $this->firstResult = $first;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(DataSourceExtensionInterface $extension)
    {
        $this->dirty = true;
        $this->extensions[] = $extension;

        foreach ((array) $extension->loadSubscribers() as $subscriber) {
            $this->eventDispatcher->addSubscriber($subscriber);
        }

        foreach ((array) $extension->loadDriverExtensions() as $driverExtension) {
            if (in_array($this->driver->getType(), $driverExtension->getExtendedDriverTypes())) {
                $this->driver->addExtension($driverExtension);
            }
        }
        return $this;
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
        $view = new DataSourceView($this);

        //PreBuildView event.
        $event = new DataSourceEvent\ViewEventArgs($this, $view);
        $this->eventDispatcher->dispatch(DataSourceEvents::PRE_BUILD_VIEW, $event);

        foreach ($this->fields as $key => $field) {
            $view->addField($field->createView());
        }

        $this->view = $view;

        //PostBuildView event.
        $event = new DataSourceEvent\ViewEventArgs($this, $view);
        $this->eventDispatcher->dispatch(DataSourceEvents::POST_BUILD_VIEW, $event);

        return $this->view;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        $this->checkFieldsClarity();
        if (isset($this->cache['parameters'])) {
            return $this->cache['parameters'];
        }

        $parameters = [];

        //PreGetParameters event.
        $event = new DataSourceEvent\ParametersEventArgs($this, $parameters);
        $this->eventDispatcher->dispatch(DataSourceEvents::PRE_GET_PARAMETERS, $event);
        $parameters = $event->getParameters();

        foreach ($this->fields as $field) {
            $field->getParameter($parameters);
        }

        //PostGetParameters event.
        $event = new DataSourceEvent\ParametersEventArgs($this, $parameters);
        $this->eventDispatcher->dispatch(DataSourceEvents::POST_GET_PARAMETERS, $event);
        $parameters = $event->getParameters();

        $cleanfunc = function (array $array) use (&$cleanfunc) {
            $newArray = [];
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $newValue = $cleanfunc($value);
                    if (!empty($newValue)) {
                        $newArray[$key] = $newValue;
                    }
                } elseif (is_scalar($value) && (!empty($value) || is_numeric($value))) {
                    $newArray[$key] = $value;
                }
            }
            return $newArray;
        };

        //Clearing parameters from empty values.
        $parameters = $cleanfunc($parameters);

        $this->cache['parameters'] = $parameters;
        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllParameters()
    {
        if ($this->factory) {
            return $this->factory->getAllParameters();
        }
        return $this->getParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function getOtherParameters()
    {
        if ($this->factory) {
            return $this->factory->getOtherParameters($this);
        }
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setFactory(DataSourceFactoryInterface $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Checks if from last time some of data has changed, and if did, resets cache.
     */
    private function checkFieldsClarity()
    {
        //Initialize with dirty flag.
        $dirty = $this->dirty;
        foreach ($this->getFields() as $field) {
            $dirty = $dirty || $field->isDirty();
        }

        //If flag was set to dirty, or any of fields was dirty, reset cache.
        if ($dirty) {
            $this->cache = [];
            $this->dirty = false;
        }
    }
}
