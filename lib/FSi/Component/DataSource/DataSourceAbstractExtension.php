<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource;

/**
 * {@inheritdoc}
 */
abstract class DataSourceAbstractExtension implements DataSourceExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function preBindParameters(DataSourceInterface $datasource, &$data)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function postBindParameters(DataSourceInterface $datasource)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function preGetResult(DataSourceInterface $datasource)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function postGetResult(DataSourceInterface $datasource, &$result)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function preBuildView(DataSourceInterface $datasource, DataSourceViewInterface $view)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function postBuildView(DataSourceInterface $datasource, DataSourceViewInterface $view)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function preGetParameters(DataSourceInterface $datasource, &$data)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function postGetParameters(DataSourceInterface $datasource, &$data)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function loadDriverExtensions()
    {
        return array();
    }
}