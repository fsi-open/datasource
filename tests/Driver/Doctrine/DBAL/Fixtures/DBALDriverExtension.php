<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\DBAL\Fixtures;

use Doctrine\DBAL\Query\QueryBuilder;
use FSi\Component\DataSource\Driver\DriverAbstractExtension;
use FSi\Component\DataSource\Event\DriverEvents;

class DBALDriverExtension extends DriverAbstractExtension
{
    /**
     * @var array
     */
    private $calls = [];

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function getExtendedDriverTypes(): array
    {
        return ['doctrine-dbal'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DriverEvents::PRE_GET_RESULT => ['preGetResult', 128],
            DriverEvents::POST_GET_RESULT => ['postGetResult', 128],
        ];
    }

    /**
     * Returns array of calls.
     *
     * @return array
     */
    public function getCalls()
    {
        return $this->calls;
    }

    /**
     * Resets calls.
     */
    public function resetCalls()
    {
        $this->calls = [];
    }

    /**
     * Catches called method.
     */
    public function __call(string $name, array $arguments)
    {
        if ('preGetResult' === $name) {
            $event = array_shift($arguments);
            $this->queryBuilder = $event->getDriver()->getQueryBuilder();
        }
        $this->calls[] = $name;
    }

    public function loadSubscribers(): array
    {
        return [$this];
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
}
