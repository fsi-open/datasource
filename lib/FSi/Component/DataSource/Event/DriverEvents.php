<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
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
    const PRE_GET_RESULT = 'datasource_driver.pre_get_result';

    const POST_GET_RESULT = 'datasource_driver.post_get_result';
}