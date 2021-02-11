<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Event;

/**
 * Enum of available events for driver.
 */
class DriverEvents
{
    /**
     * PreGetResult event name.
     */
    public const PRE_GET_RESULT = 'datasource_driver.pre_get_result';

    /**
     * PostGetResult event name.
     */
    public const POST_GET_RESULT = 'datasource_driver.post_get_result';
}
