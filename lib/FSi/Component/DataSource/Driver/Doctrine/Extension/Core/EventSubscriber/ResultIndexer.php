<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\Extension\Core\EventSubscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FSi\Component\DataIndexer\DoctrineDataIndexer;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Event\DriverEvent\ResultEventArgs;
use FSi\Component\DataSource\Event\DriverEvents;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class contains method called at BindParameters events.
 */
class ResultIndexer implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Bridge\Doctrine\ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(DriverEvents::POST_GET_RESULT => array('postBindResult', 1024));
    }

    /**
     * @param ResultEventArgs $event
     */
    public function postBindResult(ResultEventArgs $event)
    {
        $result = $event->getResult();

        if ($result instanceof Paginator) {
            $result = $result->getIterator();
        }

        if (count($result)) {
            $firstElement = current($result);

            if (is_object($firstElement)) {
                $dataClass = get_class($firstElement);
                $dataIndexer = new DoctrineDataIndexer($this->registry, $dataClass);
                $collection = new ArrayCollection();

                foreach ($result as $object) {
                    $index = $dataIndexer->getIndex($object);
                    $collection->set($index, $object);
                }

                $event->setResult($collection);
            }
        }
    }
}
