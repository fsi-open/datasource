<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine;

use Doctrine\ORM\EntityManager;

/**
 * {@inheritdoc}
 */
class DoctrineFactory implements DoctrineFactoryInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Array of extensions.
     *
     * @var array
     */
    private $extensions;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     * @param array $extensions array of extensions
     */
    public function __construct(EntityManager $em, $extensions = array())
    {
        $this->em = $em;
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver($entity, $alias = null)
    {
        return new DoctrineDriver($this->extensions, $this->em, $entity, $alias);
    }
}
