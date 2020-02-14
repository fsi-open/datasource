<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource;

use ArrayIterator;
use Doctrine\Common\Collections\ArrayCollection;
use FSi\Component\DataSource\Exception\DataSourceViewException;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Util\AttributesContainer;
use InvalidArgumentException;
use function count;

class DataSourceView extends AttributesContainer implements DataSourceViewInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $otherParameters = [];

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var ArrayIterator
     */
    private $iterator;

    /**
     * @var ArrayCollection
     */
    private $result;

    public function __construct(DataSourceInterface $datasource)
    {
        $this->name = $datasource->getName();
        $this->parameters = $datasource->getParameters();
        $this->otherParameters = $datasource->getOtherParameters();
        $this->result = $datasource->getResult();
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getAllParameters()
    {
        return array_merge($this->otherParameters, $this->parameters);
    }

    public function getOtherParameters()
    {
        return $this->otherParameters;
    }

    public function hasField($name)
    {
        return isset($this->fields[$name]);
    }

    public function removeField($name)
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]->setDataSourceView(null);
            unset($this->fields[$name]);
        }
        return $this;
    }

    public function getField($name)
    {
        if (false === $this->hasField($name)) {
            throw new DataSourceViewException("There's no field with name \"{$name}\"");
        }

        return $this->fields[$name];
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function clearFields()
    {
        $this->fields = [];

        return $this;
    }

    public function addField(FieldViewInterface $fieldView)
    {
        $name = $fieldView->getName();
        if ($this->hasField($name)) {
            throw new DataSourceViewException("There's already field with name \"{$name}\"");
        }

        $this->fields[$name] = $fieldView;
        $fieldView->setDataSourceView($this);
        $this->iterator = null;
        return $this;
    }

    public function setFields(array $fields)
    {
        $this->fields = [];

        foreach ($fields as $field) {
            if (false === $field instanceof FieldViewInterface) {
                throw new InvalidArgumentException(
                    sprintf('Field must implement %s', FieldViewInterface::class)
                );
            }

            $this->fields[$field->getName()] = $field;
            $field->setDataSourceView($this);
        }
        $this->iterator = null;

        return $this;
    }

    /**
     * Implementation of \ArrayAccess interface method.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->fields[$offset]);
    }

    /**
     * Implementation of \ArrayAccess interface method.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->fields[$offset];
    }

    /**
     * Implementation of \ArrayAccess interface method.
     *
     * In fact it does nothing - view shouldn't set its fields in this way.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * In fact it does nothing - view shouldn't unset its fields in this way.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * @return integer
     */
    public function count()
    {
        return count($this->fields);
    }

    /**
     * @param integer $position
     */
    public function seek($position)
    {
        $this->initIterator();

        return $this->iterator->seek($position);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->initIterator();

        return $this->iterator->current();
    }

    /**
     * @return mixed
     */
    public function key()
    {
        $this->initIterator();

        return $this->iterator->key();
    }

    public function next()
    {
        $this->initIterator();

        return $this->iterator->next();
    }

    public function rewind()
    {
        $this->initIterator();

        return $this->iterator->rewind();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $this->initIterator();

        return $this->iterator->valid();
    }

    public function getResult()
    {
        return $this->result;
    }

    private function initIterator()
    {
        if (!isset($this->iterator)) {
            $this->iterator = new ArrayIterator($this->fields);
        }
    }
}
