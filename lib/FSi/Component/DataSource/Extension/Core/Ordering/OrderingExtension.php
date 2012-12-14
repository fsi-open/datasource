<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Ordering;

use FSi\Component\DataSource\DataSourceAbstractExtension;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;

/**
 * Ordering extension allows to set orderings for fetched data.
 *
 * It also sets proper ordering priority just before fetching data. It's up to driver
 * to 'catch' these priorities and make it work.
 */
class OrderingExtension extends DataSourceAbstractExtension
{
    /**
     * Key for passing data and ordering attribute.
     */
    const ORDERING = 'ordering';

    /**
     * Key for ordering priority attribute.
     */
    const ORDERING_PRIORITY = 'ordering_priority';

    /**
     * Key for current ordering priority attribute.
     */
    const CURRENT_PRIORITY = 'ordering_current_priority';

    /**
     * Pattern for names.
     */
    const PATTERN = '%s[%s][%s][%s]';

    /**
     * Key for internal use, to determine if there were parameters given for field.
     */
    const ORDERING_IS_GIVEN = 'ordering_given';

    /**
     * Key for ordering disabled option.
     */
    const ORDERING_IS_DISABLED = 'ordering_disabled';

    /**
     * Key for ordering pattern.
     */
    const VIEW_PATTERN_ORDERING = 'ordering_pattern';

    /**
     * Key for ordering priority attribute.
     */
    const VIEW_PATTERN_PRIORITY = 'ordering_priority_pattern';

    /**
     * Key for next priority attribute.
     */
    const VIEW_NEXT_PRIORITY = 'ordering_next_priority';

    /**
     * Key for 'is enabled' attribute.
     */
    const VIEW_IS_ENABLED = 'ordering_enabled';

    /**
     * Key for current attribute.
     */
    const VIEW_CURRENT_ORDERING = 'ordering_current';

    /**
     * Key to determine if ordering is disabled.
     */
    const VIEW_ORDERING_DISABLED = 'ordering_disabled';

    /**
     * {@inheritdoc}
     */
    public function loadDriverExtensions()
    {
        return array(
            new Driver\DriverExtension(),
        );
    }

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
