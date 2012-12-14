<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Symfony\Form;

use FSi\Component\DataSource\DataSourceAbstractExtension;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use Symfony\Component\Form\FormFactory;

/**
 * Form extension builds Symfony form for given datasource fields.
 *
 * Extension also maintains replacing parameters that came into request into proper form,
 * replacing these parameters into scalars while getting parameters and sets proper
 * options to view.
 */
class FormExtension extends DataSourceAbstractExtension
{
    /**
     * Attribute name for form.
     */
    const VIEW_FORM = 'form';

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * Constructor.
     *
     * @param FormFactory $formFactory
     */
    public function __construct(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadDriverExtensions()
    {
        return array(
            new Driver\DriverExtension($this->formFactory),
        );
    }
}
