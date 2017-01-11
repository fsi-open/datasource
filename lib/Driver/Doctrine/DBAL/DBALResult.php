<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use FSi\Component\DataIndexer\DoctrineDataIndexer;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Paginator;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DBALResult extends ArrayCollection
{
    /**
     * @var int
     */
    private $count;

    /**
     * @param string|\Closure $indexField
     */
    public function __construct(Paginator $paginator, $indexField)
    {
        if (!is_string($indexField) && !$indexField instanceof \Closure) {
            throw new \InvalidArgumentException(sprintf(
                'indexField should be string or \Closure but is %s',
                is_object($indexField) ? 'an instance of ' . get_class($indexField) : gettype($indexField)
            ));
        }

        $result = array();
        $this->count = $paginator->count();
        $data = $paginator->getIterator();

        $propertyAccessor = new PropertyAccessor();

        if (count($data)) {
            foreach ($data as $key => $element) {
                if (is_string($indexField)) {
                    $index = $propertyAccessor->getValue($element, $indexField);
                } else {
                    $index = $indexField($element);
                }
                if (is_null($index)) {
                    throw new \RuntimeException('Index cannot be null');
                }
                if (array_key_exists($index, $result)) {
                    throw new \RuntimeException(sprintf('Duplicate index "%s"', $index));
                }

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
