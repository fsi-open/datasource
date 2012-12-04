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
     * Key for passing data.
     */
    const ORDERING = 'ordering';

    /**
     * Key for ordering option.
     */
    const ORDERING_OPTION = 'ordering_type';

    /**
     * Key for ordering priority option.
     */
    const ORDERING_PRIORITY_OPTION = 'ordering_priority';

    /**
     * Key for ordering pattern.
     */
    const PATTERN_ORDERING_OPTION = 'ordering_pattern';

    /**
     * Key for ordering priority option.
     */
    const PATTERN_PRIORITY_OPTION = 'ordering_priority_pattern';

    /**
     * Key for next priority option.
     */
    const NEXT_PRIORITY_OPTION = 'ordering_next_priority';

    /**
     * Key for 'is enabled' option.
     */
    const IS_ENABLED_OPTION = 'ordering_enabled';

    /**
     * Key for current ordering.
     */
    const CURRENT_ORDERING_OPTION = 'ordering_current';

    /**
     * Key for current ordering priority.
     */
    const CURRENT_PRIORITY_OPTION = 'ordering_current_priority';

    /**
     * Pattern for names.
     */
    const PATTERN = '%s[%s][%s][%s]';

    /**
     * Key to determine if ordering is disabled.
     */
    const ORDERING_DISABLED_OPTION = 'ordering_disabled';

    /**
     * Key for internal use, to determine if there were parameters given for field.
     */
    const ORDERING_IS_GIVEN = 'ordering_given';

    /**
     * Key for ordering disabled option.
     */
    const ORDERING_IS_DISABLED = 'ordering_disabled';

    /**
     * Key to reset page.
     */
    const RESET_PAGE_OPTION = 'resetpage';

    /**
     * @var int
     */
    private $nextPriority;

    /**
     * @var bool
     */
    private $resetPage = false;

    /**
     * {@inheritdoc}
     */
    public function preBindParameters(DataSourceInterface $datasource, &$data)
    {
        $datasourceName = $datasource->getName();

        if (
            isset($data[$datasourceName])
            && isset($data[$datasourceName][self::ORDERING])
            && isset($data[$datasourceName][self::ORDERING][self::RESET_PAGE_OPTION])
        ) {
            unset($data[$datasourceName][self::ORDERING][self::RESET_PAGE_OPTION]);
            $this->resetPage = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postBindParameters(DataSourceInterface $datasource)
    {
        if ($this->resetPage) {
            $datasource->setFirstResult(0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postBuildView(DataSourceInterface $datasource, DataSourceViewInterface $view)
    {
        $this->countNextPriority($datasource);
        $view->setOption(self::NEXT_PRIORITY_OPTION, $this->nextPriority);

        $datasourceName = $datasource->getName();
        $view->setOption(self::PATTERN_ORDERING_OPTION, sprintf(self::PATTERN, $datasourceName, self::ORDERING, '%s', self::ORDERING_OPTION));
        $view->setOption(self::PATTERN_PRIORITY_OPTION, sprintf(self::PATTERN, $datasourceName, self::ORDERING, '%s', self::ORDERING_PRIORITY_OPTION));
        $view->setOption(self::RESET_PAGE_OPTION, sprintf('%s[%s][%s]', $datasourceName, self::ORDERING, self::RESET_PAGE_OPTION));
    }

    /**
     * {@inheritdoc}
     */
    public function preGetResult(DataSourceInterface $datasource)
    {
        $this->countNextPriority($datasource);
        $resultBasic = array();
        $endBasic = array();
        $resultGiven = array();
        $endGiven = array();

        foreach ($datasource->getFields() as $field) {
            if ($field->hasOption(self::ORDERING_IS_GIVEN) && $field->getOption(self::ORDERING_IS_GIVEN)) {
                $result = &$resultGiven;
                $end = &$endGiven;
            } else {
                $result = &$resultBasic;
                $end = &$endBasic;
            }

            $options = $field->getOptions();
            if (isset($options[self::ORDERING_PRIORITY_OPTION])) {
                $priority = (int) $options[self::ORDERING_PRIORITY_OPTION];
            } else {
                $end[] = array('field' => $field);
                continue;
            }

            $i = 0;
            foreach ($result as $item) {
                if ($item['priority'] < $priority) {
                    break;
                }
                $i++;
            }

            array_splice($result, $i, 0, array(array('priority' => $priority, 'field' => $field)));
        }

        $fields = array_merge($resultGiven, $endGiven, $resultBasic, $endBasic);

        $max = count($fields);
        foreach ($fields as $item) {
            $field = $item['field'];
            $options = $field->getOptions();
            $options[self::ORDERING_PRIORITY_OPTION] = $max;
            $field->setOptions($options);
            $max--;
        }
    }

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
     * Counts next priority for orderings.
     *
     * @param DataSourceInterface $datasource
     */
    private function countNextPriority(DataSourceInterface $datasource)
    {
        if (isset($this->nextPriority)) {
            return;
        }

        $next = 0;
        foreach ($datasource->getFields() as $field) {
            if ($field->hasOption(self::ORDERING_IS_GIVEN) && $field->getOption(self::ORDERING_IS_GIVEN) && $field->hasOption(self::ORDERING_PRIORITY_OPTION)) {
                $tmp = (int) $field->getOption(self::ORDERING_PRIORITY_OPTION);
                if ($tmp > $next) {
                    $next = $tmp;
                }
            }
        }
        $this->nextPriority = floor($next) + 1;
    }
}