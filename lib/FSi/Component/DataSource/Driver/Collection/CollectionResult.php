<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

class CollectionResult extends ArrayCollection
{
    private $count;

    public function __construct(array $elements = array(), Criteria $criteria)
    {
        $collection = new ArrayCollection($elements);
        $firstResult = $criteria->getFirstResult();
        $maxResults = $criteria->getMaxResults();
        $criteria->setFirstResult(null);
        $criteria->setMaxResults(null);
        $collection = $collection->matching($criteria);
        $this->count = $collection->count();
        parent::__construct($collection->slice($firstResult, $maxResults));
    }

    public function count()
    {
        return $this->count;
    }
}
