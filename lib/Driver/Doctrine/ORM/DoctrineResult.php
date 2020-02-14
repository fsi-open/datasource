<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FSi\Component\DataIndexer\DoctrineDataIndexer;

class DoctrineResult extends ArrayCollection
{
    /**
     * @var int
     */
    private $count;

    public function __construct(ManagerRegistry $registry, Paginator $paginator)
    {
        $this->count = $paginator->count();
        $data = $paginator->getIterator();
        $data->rewind();

        $result = [];
        if (0 !== $data->count()) {
            $firstElement = $data->current();
            $dataIndexer = is_object($firstElement)
                ? new DoctrineDataIndexer($registry, get_class($firstElement))
                : null;

            foreach ($data as $key => $element) {
                $index = true === $dataIndexer instanceof DoctrineDataIndexer
                    ? $dataIndexer->getIndex($element)
                    : $key
                ;

                $result[$index] = $element;
            }
        }

        parent::__construct($result);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->count;
    }
}
