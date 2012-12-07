<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Event\FieldEvent;

use Symfony\Component\EventDispatcher\Event;
use FSi\Component\DataSource\Field\FieldTypeInterface;

/**
 * Event class for Field.
 */
class FieldEventArgs extends Event
{
    /**
     * @var FieldTypeInterface
     */
    private $field;

    /**
     * Constructor.
     *
     * @param FieldTypeInterface $field
     */
    public function __construct(FieldTypeInterface $field)
    {
        $this->field = $field;
    }

    /**
     * @return FieldTypeInterface
     */
    public function getField()
    {
        return $this->field;
    }
}
