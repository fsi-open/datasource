<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource;

/**
 * Extension of DataSource.
 */
interface DataSourceExtensionInterface
{
    /**
     * Loads events subscribers.
     *
     * Each subscriber must implements Symfony\Component\EventDispatcher\EventSubscriberInterface.
     *
     * @return array
     */
    public function loadSubscribers();

    /**
     * Allows DataSource extension to load extensions directly to its driver.
     *
     * @return array
     */
    public function loadDriverExtensions();
}