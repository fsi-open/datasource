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

use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldView;

/**
 * {@inheritdoc}
 */
class DataSource implements DataSourceInterface
{
    /**
     * Driver.
     *
     * @var Driver\DriverInterface
     */
    private $driver;

    /**
     * Name of data source.
     *
     * @var string
     */
    private $name;

    /**
     * Fields of data source.
     *
     * @var array
     */
    private $fields = array();

    /**
     * Extensions of DataSource.
     *
     * @var array
     */
    private $extensions = array();

    /**
     * @var DataSourceView
     */
    private $view;

    /**
     * @var DataSourceFactoryInterface
     */
    private $factory;
    /**
     * Cache for given data.
     *
     * Helpful when determining if criterions have any changes.
     *
     * @var null|array
     */
    private $criterions;

    /**
     * Cache for result.
     *
     * @var null|mixed
     */
    private $result;

    /**
     * Max results fetched at once.
     *
     * @var int
     */
    private $maxResults = 20;

    /**
     * Offset for first result.
     *
     * @var int
     */
    private $firstResult = 0;

    /**
     * Cache for methods that depends on fields data (cache is dropped whenever any of fields is dirty, or fields have changed).
     *
     * @var array
     */
    private $cache = array();

    /**
     * Flag set as true when fields or their data is modifying, or even new extension is added.
     *
     * @var bool
     */
    private $dirty = true;

    /**
     * Constructor.
     *
     * @param DriverInterface $driver
     * @param string $name
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
     *
     * @throws DataSourceException
     * @return DataSource
     */
    public function addField($name, $type = null, $comparison = null, $options = array())
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

        if ($this->hasField($name)) {
            throw new DataSourceException(sprintf('Name "%s" is already in use by other field.', $name));
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
            $this->fields[$name]->removeDataSource();
            unset($this->fields[$name]);
            $this->dirty = true;
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws DataSourceException
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
        foreach ($this->fields as $field) {
            $field->removeDataSource();
        }
        $this->fields = array();
        $this->dirty = true;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bindParameters($data = array())
    {
        $this->dirty = true;

        foreach ($this->extensions as $extension) {
            $extension->preBindParameters($this, $data);
        }

        if (!is_array($data)) {
            throw new DataSourceException('Given data must be an array.');
        }

        foreach ($this->getFields() as $field) {
            $field->bindParameter($data);
        }

        //Page number.
        $page = (isset($data[$this->getName()]) && isset($data[$this->getName()][self::PAGE])) ? (int) $data[$this->getName()][self::PAGE] : 1;

        $this->setFirstResult(($page - 1) * $this->getMaxResults());

        foreach ($this->extensions as $extension) {
            $extension->postBindParameters($this);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws DataSourceException
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

        foreach ($this->extensions as $extension) {
            $extension->preGetResult($this);
        }

        $result = $this->driver->getResult($this->fields, $this->getFirstResult(), $this->getMaxResults());

        foreach ($this->getFields() as $field) {
            $field->setDirty(false);
        }

        if (!is_object($result)) {
            throw new DataSourceException('Returned result must be object implementing both Conutable and IteratorAggregate.');
        }

        if ((!$result instanceof \IteratorAggregate) || (!$result instanceof \Countable)) {
            throw new DataSourceException(sprintf('Returned result must be both Countable and IteratorAggregate, instance of "%s" given.', get_class($result)));
        }

        foreach ($this->extensions as $extension) {
            $extension->postGetResult($this, $result);
        }

        //Creating cache.
        $this->cache['result'] = array(
            'result' => $result,
            'firstresult' => $this->getFirstResult(),
            'maxresults' => $this->getMaxResults(),
        );

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMaxResults($max)
    {
        $this->dirty = true;
        $this->maxResults = $max;
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstResult($first)
    {
        $this->dirty = true;
        $this->firstResult = $first;
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
        foreach ($extension->loadDriverExtensions() as $driverExtension) {
            $this->driver->addExtension($driverExtension);
        }
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
        foreach ($this->extensions as $extension) {
            $extension->preBuildView($this, $view);
        }

        foreach ($this->fields as $key => $field) {
            $view->addField($field->createView());
        }

        $this->view = $view;

        foreach ($this->extensions as $extension) {
            $extension->postBuildView($this, $view);
        }

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

        //Fetching parameters.
        $parameters = array();

        //preGetParameters event.
        foreach ($this->extensions as $extension) {
            $extension->preGetParameters($this, $parameters);
        }

        foreach ($this->fields as $field) {
            $field->getParameter($parameters);
        }

        //postGetParameters event.
        foreach ($this->extensions as $extension) {
            $extension->postGetParameters($this, $parameters);
        }

        $cleanfunc = function(&$value) use (&$cleanfunc) {
            if (is_scalar($value) && (!empty($value) || is_numeric($value))) {
                return true;
            } elseif (is_array($value)) {
                $value = array_filter($value, $cleanfunc);
                return !empty($value);
            } else {
                return false;
            }
        };

        //Clearing parameters from empty values.
        $parameters = array_filter($parameters, $cleanfunc);

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
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function setFactory(DataSourceFactoryInterface $factory)
    {
        $this->factory = $factory;
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
            $this->cache = array();
            $this->dirty = false;
        }
    }
}