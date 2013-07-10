<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FSi\Component\DataIndexer\DoctrineDataIndexer;

class DoctrineResult extends ArrayCollection
{
    private $count;

    public function __construct(ManagerRegistry $registry, Paginator $paginator)
    {
        $result = array();
        $this->count = $paginator->count();
        $data = $paginator->getIterator();

        if (count($data)) {
            $firstElement = current($data);
            $dataIndexer =  is_object($firstElement)
                ? new DoctrineDataIndexer($registry, get_class($firstElement))
                : null;

            foreach ($data as $key => $element) {
                $index = isset($dataIndexer) ? $dataIndexer->getIndex($element) : $key;
                $result[$index] = $element;
            }
        }
        
        parent::__construct($result);
    }

    public function count()
    {
        return $this->count;
    }
}
