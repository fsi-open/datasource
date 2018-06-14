<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Collection;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Traversable;

class CollectionResult implements Countable, \IteratorAggregate, ArrayAccess
{
    /**
     * @var int
     */
    private $count;

    /**
     * @var ArrayCollection
     */
    private $collection;

    /**
     * @param array|Traversable|Selectable $collection
     * @param Criteria $criteria
     */
    public function __construct($collection, Criteria $criteria)
    {
        if ($collection instanceof Selectable) {
            $collection = $collection->matching(new Criteria());
        } elseif ($collection instanceof Traversable) {
            $collection = new ArrayCollection(iterator_to_array($collection));
        } elseif (is_array($collection)) {
            $collection = new ArrayCollection($collection);
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Provided collection type "%s" should be Selectable or/and Traversable or array',
                    \is_object($collection) ? \get_class($collection) : \gettype($collection)
                )
            );
        }

        $this->collection = $collection->matching($criteria);

        $criteria->setFirstResult(null);
        $criteria->setMaxResults(null);
        $this->count = \count($collection->matching($criteria));
    }

    public function count()
    {
        return $this->count;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->collection->toArray());
    }

    public function offsetExists($offset)
    {
        return $this->collection->containsKey($offset);
    }

    public function offsetGet($offset)
    {
        return $this->collection->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->collection->add($value);
            return;
        }

        $this->collection->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->collection->remove($offset);
    }

    public function first()
    {
        return $this->collection->first();
    }
}
