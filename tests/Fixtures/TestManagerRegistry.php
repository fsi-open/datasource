<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Fixtures;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

/**
 * It's dumb implementation of ManagerRegistry, but it's enough for testing purposes.
 */
class TestManagerRegistry implements ManagerRegistry
{
    /**
     * Test managers name.
     */
    private const NAME = 'test';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultManagerName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConnectionName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection($name = null)
    {
        return $this->em;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnections()
    {
        return [$this->em];
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionNames()
    {
        return [self::NAME];
    }


    /**
     * {@inheritdoc}
     */
    public function getManager($name = null)
    {
        return $this->em;
    }

    /**
     * {@inheritdoc}
     */
    public function getManagers()
    {
        return [$this->em];
    }

    /**
     * {@inheritdoc}
     */
    public function resetManager($name = null)
    {
        return $this->em;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasNamespace($alias)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerNames()
    {
        return [self::NAME];
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        return $this->em;
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerForClass($class)
    {
        return $this->em;
    }
}
