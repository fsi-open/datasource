<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\DBAL\Extension\Core\EventSubscriber;

use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALResult;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\Paginator;
use FSi\Component\DataSource\Event\DriverEvent\ResultEventArgs;
use FSi\Component\DataSource\Event\DriverEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class contains method called at BindParameters events.
 */
class ResultIndexer implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(DriverEvents::POST_GET_RESULT => array('postGetResult', 1024));
    }

    /**
     * @param \FSi\Component\DataSource\Event\DriverEvent\ResultEventArgs $event
     */
    public function postGetResult(ResultEventArgs $event)
    {
        /** @var DBALDriver $driver */
        $driver = $event->getDriver();
        $indexField = $driver->getIndexField();

        if (empty($indexField)) {
            return;
        }

        $result = $event->getResult();

        if ($result instanceof Paginator) {
            $event->setResult(new DBALResult($result, $indexField));
        }
    }
}
