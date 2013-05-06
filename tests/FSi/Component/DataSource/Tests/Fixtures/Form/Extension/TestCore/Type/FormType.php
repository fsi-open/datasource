<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Bartosz Bialek <bartosz.bialek@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Fixtures\Form\Extension\TestCore\Type;

use Symfony\Component\Form\Extension\Core\Type\FormType as BaseFormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class FormType extends BaseFormType
{
    public function __construct(PropertyAccessorInterface $propertyAccessor = null)
    {
        parent::__construct($propertyAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['type'] = $form->getConfig()->getType()->getName();
    }
}
