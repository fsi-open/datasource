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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Extension of DataSources field.
 */
interface FieldExtensionInterface
{
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

    /**
     * Allows extension to load constraints to fields OptionsResolver. Called by field.
     *
     * @param OptionsResolverInterface $optionsResolver
     */
    public function loadOptionsConstraints(OptionsResolverInterface $optionsResolver);
}
