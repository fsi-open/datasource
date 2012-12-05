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
     * Key for passing data and ordering attribute.
     */
    const ORDERING = 'ordering';

    /**
     * Key for ordering priority attribute.
     */
    const ORDERING_PRIORITY = 'ordering_priority';

    /**
     * Key for ordering pattern.
     */
    const PATTERN_ORDERING = 'ordering_pattern';

    /**
     * Key for ordering priority attribute.
     */
    const PATTERN_PRIORITY = 'ordering_priority_pattern';

    /**
     * Key for next priority attribute.
     */
    const NEXT_PRIORITY = 'ordering_next_priority';

    /**
     * Key for 'is enabled' attribute.
     */
    const IS_ENABLED = 'ordering_enabled';

    /**
     * Key for current attribute.
     */
    const CURRENT_ORDERING = 'ordering_current';

    /**
     * Key for current ordering priority attribute.
     */
    const CURRENT_PRIORITY = 'ordering_current_priority';

    /**
     * Pattern for names.
     */
    const PATTERN = '%s[%s][%s][%s]';

    /**
     * Key to determine if ordering is disabled.
     */
    const ORDERING_DISABLED = 'ordering_disabled';

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
    const RESET_PAGE = 'resetpage';

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
            && isset($data[$datasourceName][self::ORDERING][self::RESET_PAGE])
        ) {
            unset($data[$datasourceName][self::ORDERING][self::RESET_PAGE]);
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
        $view->setAttribute(self::NEXT_PRIORITY, $this->nextPriority);

        $datasourceName = $datasource->getName();
        $view->setAttribute(self::PATTERN_ORDERING, sprintf(self::PATTERN, $datasourceName, self::ORDERING, '%s', self::ORDERING));
        $view->setAttribute(self::PATTERN_PRIORITY, sprintf(self::PATTERN, $datasourceName, self::ORDERING, '%s', self::ORDERING_PRIORITY));
        $view->setAttribute(self::RESET_PAGE, sprintf('%s[%s][%s]', $datasourceName, self::ORDERING, self::RESET_PAGE));
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
            if (isset($options[self::ORDERING_PRIORITY])) {
                $priority = (int) $options[self::ORDERING_PRIORITY];
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
            $options[self::ORDERING_PRIORITY] = $max;
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
            if ($field->hasOption(self::ORDERING_IS_GIVEN) && $field->getOption(self::ORDERING_IS_GIVEN) && $field->hasOption(self::ORDERING_PRIORITY)) {
                $tmp = (int) $field->getOption(self::ORDERING_PRIORITY);
                if ($tmp > $next) {
                    $next = $tmp;
                }
            }
        }
        $this->nextPriority = floor($next) + 1;
    }
}