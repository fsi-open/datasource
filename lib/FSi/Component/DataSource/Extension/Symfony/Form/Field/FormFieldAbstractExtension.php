<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Symfony\Form\Field;

use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldViewInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\DataSourceInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormBuilder;
use FSi\Component\DataSource\Extension\Symfony\Form\FormExtension;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Event\DataSourceFieldEventInterface;

/**
 * Base extension for fields extensions.
 */
abstract class FormFieldAbstractExtension extends FieldAbstractExtension implements EventSubscriberInterface
{
    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var \Symfony\Component\Form\Form
     */
    protected $form;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FieldEvents::PRE_BIND_PARAMETER => array('preBindParameter', 128),
            FieldEvents::POST_BUILD_VIEW => array('postBuildView', 128),
        );
    }

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
    public function postBuildView(FieldEvent\ViewEventArgs $event)
    {
        $field = $event->getField();
        $view = $event->getView();

        $this->createForm($field);
        $view->setAttribute(FormExtension::FORM, $this->form->createView());
    }

    /**
     * {@inheritdoc}
     */
    public function preBindParameter(FieldEvent\ParameterEventArgs $event)
    {
        $field = $event->getField();
        $parameter = $event->getParameter();

        if ($field->hasOption('form_disabled') && $field->getOption('form_disabled')) {
            return;
        }

        $this->createForm($field);
        if ($this->form->isBound()) {
            unset($this->form);
            $this->createForm($field);
        }

        $datasourceName = $field->getDataSource() ? $field->getDataSource()->getName() : null;
        if (empty($datasourceName)) {
            return;
        }

        if (
            is_array($parameter)
            && isset($parameter[$datasourceName])
            && isset($parameter[$datasourceName][DataSourceInterface::FIELDS])
            && isset($parameter[$datasourceName][DataSourceInterface::FIELDS][$field->getName()])
        ) {
            $dataToBind = array(
                DataSourceInterface::FIELDS => array(
                    $field->getName() => $parameter[$datasourceName][DataSourceInterface::FIELDS][$field->getName()],
                ),
            );
        } else {
            $dataToBind = array();
        }

        $this->form->bind($dataToBind);
        $parameter = $this->arrayMergeRecursive($parameter, array($datasourceName => $this->form->getData()));
        $event->setParameter($parameter);
    }

    /**
     * Builds form.
     *
     * @param FieldTypeInterface $field
     */
    protected function createForm(FieldTypeInterface $field)
    {
        if (isset($this->form)) {
            return;
        }

        if (!$datasource = $field->getDataSource()) {
            return;
        }

        $options = $field->hasOption('form_options') ? (array) $field->getOption('form_options') : array();
        $options = array_merge($options, array('required' => false));

        $form = $this->formFactory->createNamedBuilder($datasource->getName(), 'collection', array(), array('csrf_protection' => false))->getForm();
        $builder = $this->formFactory->createNamedBuilder(DataSourceInterface::FIELDS);

        $this->buildForm($field, $builder, $options);

        $form->add($builder->getForm());
        $this->form = $form;
    }

    /**
     * @return FormFactory
     */
    protected function getFormFactory()
    {
        return $this->formFactory;
    }

    /**
     * Method that has to handle field addition.
     *
     * @param FieldTypeInterface $field
     * @param FormBuilder $builder
     * @param array $options
     */
    abstract protected function buildForm(FieldTypeInterface $field, FormBuilder $builder, $options);

    /**
     * Method for mergin arrays in a little bit different way than standard PHP function.
     *
     * @param array $array
     * @return array
     */
    private function arrayMergeRecursive()
    {
        $arrays = func_get_args();
        $merged = array();
        while ($arrays) {
            $array = array_shift($arrays);
            if (!is_array($array)) {
                $array = (array) $array;
            }
            if (!$array) {
                continue;
            }

            foreach ($array as $key => $value) {
                if (is_string($key)) {
                    if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
                        $merged[$key] = $this->arrayMergeRecursive($merged[$key], $value);
                    } else {
                        $merged[$key] = $value;
                    }
                } else {
                    $merged[] = $value;
                }
            }
        }
        return $merged;
    }

    /**
     * {@inheritdoc}
     */
    public function loadSubscribers()
    {
        return array($this);
    }
}