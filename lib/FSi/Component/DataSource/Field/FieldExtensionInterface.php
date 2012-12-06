<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Field;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;

/**
 * Extension of DataSources field.
 */
interface FieldExtensionInterface
{
    /**
     * Returns array of available options.
     *
     * @return array
     */
    public function getAvailableOptions();

    /**
     * Returns array of default available options.
     *
     * @return array
     */
    public function getDefaultAvailableOptions();

    /**
     * Returns array of required options.
     *
     * @return array
     */
    public function getRequiredOptions();

    /**
     * Returns array of default required options.
     *
     * @return array
     */
    public function getDefaultRequiredOptions();

    /**
     * Returns array of extended types.
     *
     * @return array
     */
    public function getExtendedFieldTypes();

    /**
     * Loads events subscribers.
     *
     * Each subscriber must implements Symfony\Component\EventDispatcher\EventSubscriberInterface.
     *
     * @return array
     */
    public function loadSubscribers();
}