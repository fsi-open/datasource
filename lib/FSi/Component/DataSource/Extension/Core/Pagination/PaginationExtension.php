<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Pagination;

use FSi\Component\DataSource\DataSourceAbstractExtension;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use Symfony\Component\Form\FormFactory;

/**
 * Pagination extension adds to view some options helpfull during view rendering.
 */
class PaginationExtension extends DataSourceAbstractExtension
{
    /**
     * Key for page info.
     */
    const PARAMETER_PAGE = 'page';

    /**
     * {@inheritdoc}
     */
    public function loadSubscribers()
    {
        return array(
            new EventSubscriber\Events(),
        );
    }
}
