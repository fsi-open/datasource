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
 * Enum of available events for field.
 */
class DataSourceFieldEvents
{
    const PRE_BIND_PARAMETER = 'datasource_field.pre_bind_parameter';

    const POST_BIND_PARAMETER = 'datasource_field.post_bind_parameter';

    const PRE_GET_PARAMETER = 'datasource_field.pre_get_parameter';

    const POST_GET_PARAMETER = 'datasource_field.post_get_parameter';

    const PRE_BUILD_VIEW = 'datasource_field.pre_build_view';

    const POST_BUILD_VIEW = 'datasource_field.post_build_view';
}