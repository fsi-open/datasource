<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\Extension\Core;

use FSi\Component\DataSource\Driver\DriverAbstractExtension;

/**
 * Core extension for Doctrine driver.
 */
class CoreExtension extends DriverAbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedDriverTypes()
    {
        return array('doctrine');
    }

    /**
     * {@inheritdoc}
     */
    protected function loadFieldTypes()
    {
        return array(
            new Field\Text(),
            new Field\Number(),
            new Field\Date(),
            new Field\Time(),
            new Field\DateTime(),
            new Field\Entity(),
        );
    }
}