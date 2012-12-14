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

use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;

/**
 * Event class for Field.
 */
class ViewEventArgs extends FieldEventArgs
{
    /**
     * @var DataSourceViewInterface
     */
    private $view;

    /**
     * Constructor.
     *
     * @param FieldTypeInterface $field
     * @param FieldViewInterface $view
     */
    public function __construct(FieldTypeInterface $field, FieldViewInterface $view)
    {
        parent::__construct($field);
        $this->view = $view;
    }

    /**
     * @return FieldViewInterface
     */
    public function getView()
    {
        return $this->view;
    }
}
