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
     * Page number option name.
     */
    const PAGE_PARAM_NAME_OPTION = 'page_param_name';

    /**
     * Current page option name.
     */
    const PAGE_CURRENT_OPTION = 'page_current';

    /**
     * Pages amount option name.
     */
    const PAGE_AMOUNT_OPTION = 'page_amount';

    /**
     * {@inheritdoc}
     */
    public function preGetParameters(DataSourceInterface $datasource, &$data)
    {
        $datasourceName = $datasource->getName();
        $maxresults = $datasource->getMaxResults();
        if ($maxresults == 0) {
            $page = 1;
        } else {
            $current = $datasource->getFirstResult();
            $page = floor($current/$maxresults) + 1;
        }

        if ($page != 1) {
            $data[$datasourceName][DataSourceInterface::PAGE] = $page;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postBuildView(DataSourceInterface $datasource, DataSourceViewInterface $view)
    {
        $view->setOption(self::PAGE_PARAM_NAME_OPTION, sprintf('%s[%s]', $datasource->getName(), DataSourceInterface::PAGE));

        $maxresults = $datasource->getMaxResults();
        if ($maxresults == 0) {
            $all = 1;
        } else {
            $all = ceil(count($datasource->getResult())/$maxresults);
        }

        $params = $datasource->getParameters();
        $datasourceName = $datasource->getName();
        $page = isset($params[$datasourceName]) && isset($params[$datasourceName][DataSourceInterface::PAGE]) ? $params[$datasourceName][DataSourceInterface::PAGE] : 1;
        $view->setOption(self::PAGE_AMOUNT_OPTION, $all);
        $view->setOption(self::PAGE_CURRENT_OPTION, $page);
    }
}