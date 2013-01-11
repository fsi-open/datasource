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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Fields extension.
 */
class FormFieldExtension extends FieldAbstractExtension
{
    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var array
     */
    protected $forms = array();

    /**
     * Original values of input parameters for each supported field
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FieldEvents::PRE_BIND_PARAMETER => array('preBindParameter'),
            FieldEvents::POST_BUILD_VIEW => array('postBuildView'),
            FieldEvents::PRE_GET_PARAMETER => array('preGetParameter'),
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
    public function getExtendedFieldTypes()
    {
        return array('text', 'number', 'date', 'time', 'datetime', 'entity');
    }

    /**
     * {@inheritdoc}
     */
    public function loadOptionsConstraints(OptionsResolverInterface $optionsResolver)
    {
        $optionsResolver->setDefaults(array('form_disabled' => false, 'form_options' => array()));
    }

    /**
     * {@inheritdoc}
     */
    public function postBuildView(FieldEvent\ViewEventArgs $event)
    {
        $field = $event->getField();
        $view = $event->getView();

        if ($form = $this->getForm($field))
            $view->setAttribute(FormExtension::VIEW_FORM, $form->createView());
    }

    /**
     * {@inheritdoc}
     */
    public function preBindParameter(FieldEvent\ParameterEventArgs $event)
    {
        $field = $event->getField();
        $field_oid = spl_object_hash($field);
        $parameter = $event->getParameter();

        if (!$form = $this->getForm($field))
            return;

        if ($form->isBound()) {
            $form = $this->getForm($field, true);
        }

        $datasourceName = $field->getDataSource() ? $field->getDataSource()->getName() : null;
        if (empty($datasourceName)) {
            return;
        }

        if (isset($parameter[$datasourceName][DataSourceInterface::FIELDS][$field->getName()])) {
            $dataToBind = array(
                DataSourceInterface::FIELDS => array(
                    $field->getName() => $parameter[$datasourceName][DataSourceInterface::FIELDS][$field->getName()],
                ),
            );
            $this->parameters[$field_oid] = $parameter[$datasourceName][DataSourceInterface::FIELDS][$field->getName()];
        } else {
            $dataToBind = array();
        }

        $form->bind($dataToBind);
        $data = $form->getData();

        if (isset($data[DataSourceInterface::FIELDS][$field->getName()])) {
            $parameter[$datasourceName][DataSourceInterface::FIELDS][$field->getName()] = $data[DataSourceInterface::FIELDS][$field->getName()];
        } else {
            unset($parameter[$datasourceName][DataSourceInterface::FIELDS][$field->getName()]);
        }

        $event->setParameter($parameter);
    }

    /**
     * {@inheritdoc}
     */
    public function preGetParameter(FieldEvent\ParameterEventArgs $event)
    {
        $field = $event->getField();
        $field_oid = spl_object_hash($field);

        $datasourceName = $field->getDataSource() ? $field->getDataSource()->getName() : null;
        if (isset($this->parameters[$field_oid])) {
            $parameters = array(
                $datasourceName => array(
                    DataSourceInterface::FIELDS => array(
                        $field->getName() => $this->parameters[$field_oid]
                    )
                )
            );
            $event->setParameter($parameters);
        }
    }

    /**
     * Builds form.
     *
     * @param FieldTypeInterface $field
     * @param bool $force
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm(FieldTypeInterface $field, $force = false)
    {
        if (!$datasource = $field->getDataSource()) {
            return;
        }

        if ($field->hasOption('form_disabled') && $field->getOption('form_disabled')) {
            return;
        }

        $field_oid = spl_object_hash($field);

        if (isset($this->forms[$field_oid]) && !$force) {
            return $this->forms[$field_oid];
        }

        $options = $field->hasOption('form_options') ? (array) $field->getOption('form_options') : array();
        $options = array_merge($options, array('required' => false));

        $form = $this->formFactory->createNamedBuilder($datasource->getName(), 'collection', array(), array('csrf_protection' => false))->getForm();
        $builder = $this->formFactory->createNamedBuilder(DataSourceInterface::FIELDS);

        switch ($field->getComparison()) {
            case 'between':
                $form2 = $this->getFormFactory()->createNamedBuilder($field->getName());

                //Options assignment, allows to specify different options for each of fields.
                $fromOptions = isset($options[0]) ? $options[0] : null;
                $toOptions = isset($options[1]) ? $options[1] : null;
                if (!$fromOptions) {
                    $fromOptions = isset($options['from']) ? $options['from'] : null;
                }
                if (!$toOptions) {
                    $toOptions = isset($options['to']) ? $options['to'] : null;
                }

                unset($options[0], $options[1], $options['from'], $options['to']);

                //Checking and merging (if need) with general options.
                if (!$fromOptions) {
                    $fromOptions = $options;
                } else {
                    $fromOptions = array_merge($options, $fromOptions);
                }
                if (!$toOptions) {
                    $toOptions = $options;
                } else {
                    $toOptions = array_merge($options, $toOptions);
                }

                $form2->add('from', $field->getType(), $fromOptions);
                $form2->add('to', $field->getType(), $toOptions);
                $builder->add($form2);
                break;

            default:
                $builder->add($field->getName(), $field->getType(), $options);
        }

        $form->add($builder->getForm());
        $this->forms[$field_oid] = $form;
        return $form;
    }

    /**
     * @return FormFactory
     */
    protected function getFormFactory()
    {
        return $this->formFactory;
    }
}
