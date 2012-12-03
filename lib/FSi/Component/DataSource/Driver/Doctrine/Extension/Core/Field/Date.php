<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\Extension\Core\Field;

/**
 * Date field.
 */
class Date extends DateTimeAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormat()
    {
        return 'Y-m-d';
    }
}