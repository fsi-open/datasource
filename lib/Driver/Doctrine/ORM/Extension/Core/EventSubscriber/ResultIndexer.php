<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\EventSubscriber;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineResult;
use FSi\Component\DataSource\Event\DriverEvent\ResultEventArgs;
use FSi\Component\DataSource\Event\DriverEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResultIndexer implements EventSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public static function getSubscribedEvents()
    {
        return [DriverEvents::POST_GET_RESULT => ['postGetResult', 1024]];
    }

    public function postGetResult(ResultEventArgs $event): void
    {
        $result = $event->getResult();
        if (true === $result instanceof Paginator) {
            $event->setResult(new DoctrineResult($this->registry, $result));
        }
    }
}
