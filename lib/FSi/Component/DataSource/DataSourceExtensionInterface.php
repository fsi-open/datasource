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
     * Method called before binding parameters.
     *
     * @param DataSourceInterface $datasource
     * @param mixed &$data
     */
    public function preBindParameters(DataSourceInterface $datasource, &$data);

    /**
     * Method called after binding parameters.
     *
     * @param DataSourceInterface $datasource
     */
    public function postBindParameters(DataSourceInterface $datasource);

    /**
     * Method called before getting result.
     *
     * @param DataSourceInterface $datasource
     */
    public function preGetResult(DataSourceInterface $datasource);

    /**
     * Method called after getting result.
     *
     * @param DataSourceInterface $datasource
     * @param mixed &$result
     */
    public function postGetResult(DataSourceInterface $datasource, &$result);

    /**
     * Method called before getting parameters.
     *
     * @param DataSourceInterface $datasource
     * @param array &$data
     */
    public function preGetParameters(DataSourceInterface $datasource, &$data);

    /**
     * Method called after getting parameters.
     *
     * @param DataSourceInterface $datasource
     * @param array &$data
     */
    public function postGetParameters(DataSourceInterface $datasource, &$data);

    /**
     * Method called before building view.
     *
     * Although it has already created view given as argument, this method is called just before calling
     * buildView method on fields.
     *
     * @param DataSourceInterface $datasource
     * @param DataSourceViewInterface $view
     */
    public function preBuildView(DataSourceInterface $datasource, DataSourceViewInterface $view);

    /**
     * Method called after building view.
     *
     * @param DataSourceInterface $datasource
     * @param DataSourceViewInterface $view
     */
    public function postBuildView(DataSourceInterface $datasource, DataSourceViewInterface $view);

    /**
     * Allows DataSource extension to load extensions directly to its driver.
     *
     * @return array
     */
    public function loadDriverExtensions();
}