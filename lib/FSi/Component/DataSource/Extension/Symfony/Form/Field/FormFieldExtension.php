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
            FieldEvents::POST_GET_PARAMETER => array('preGetParameter'),
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
    public function initOptions(FieldTypeInterface $field)
    {
        $field->getOptionsResolver()
            ->setDefaults(array(
                'form_filter' => true,
                'form_options' => array(),
                'form_from_options' => array(),
                'form_to_options' =>array()
            ))
            ->setOptional(array(
                'form_type',
                'form_order'
            ))
            ->setAllowedTypes(array(
                'form_filter' => 'bool',
                'form_options' => 'array',
                'form_from_options' => 'array',
                'form_to_options' =>'array',
                'form_order' => 'integer',
                'form_type' => 'string'
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function postBuildView(FieldEvent\ViewEventArgs $event)
    {
        $field = $event->getField();
        $view = $event->getView();

        if ($form = $this->getForm($field)) {
            $view->setAttribute('form', $form->createView());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preBindParameter(FieldEvent\ParameterEventArgs $event)
    {
        $field = $event->getField();
        $field_oid = spl_object_hash($field);
        $parameter = $event->getParameter();

        if (!$form = $this->getForm($field)) {
            return;
        }

        if ($form->isBound()) {
            $form = $this->getForm($field, true);
        }

        $datasourceName = $field->getDataSource() ? $field->getDataSource()->getName() : null;

        if (empty($datasourceName)) {
            return;
        }

        if (isset($parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$field->getName()])) {
            $dataToBind = array(
                DataSourceInterface::PARAMETER_FIELDS => array(
                    $field->getName() => $parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$field->getName()],
                ),
            );
            $this->parameters[$field_oid] = $parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$field->getName()];

            $form->bind($dataToBind);
            $data = $form->getData();

            if (isset($data[DataSourceInterface::PARAMETER_FIELDS][$field->getName()])) {
                $parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$field->getName()] = $data[DataSourceInterface::PARAMETER_FIELDS][$field->getName()];
            } else {
                unset($parameter[$datasourceName][DataSourceInterface::PARAMETER_FIELDS][$field->getName()]);
            }

            $event->setParameter($parameter);
        }
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
                    DataSourceInterface::PARAMETER_FIELDS => array(
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

        if (!$field->getOption('form_filter')) {
            return;
        }

        $field_oid = spl_object_hash($field);

        if (isset($this->forms[$field_oid]) && !$force) {
            return $this->forms[$field_oid];
        }

        $options = $field->getOption('form_options');
        $options = array_merge($options, array('required' => false));

        $form = $this->formFactory->createNamedBuilder($datasource->getName(), 'collection', array(), array('csrf_protection' => false))->getForm();
        $builder = $this->formFactory->createNamedBuilder(DataSourceInterface::PARAMETER_FIELDS);

        switch ($field->getComparison()) {
            case 'between':
                $form2 = $this->getFormFactory()->createNamedBuilder($field->getName(), 'form', null, $options);

                $fromOptions = $field->getOption('form_from_options');
                $toOptions = $field->getOption('form_to_options');
                $fromOptions = array_merge($options, $fromOptions);
                $toOptions = array_merge($options, $toOptions);

                $type = $field->getType();
                if ($field->hasOption('form_type')) {
                    $type = $field->getOption('form_type');
                }
                $form2->add('from', $type, $fromOptions);
                $form2->add('to', $type, $toOptions);
                $builder->add($form2);
                break;

            case 'isNull':
                $defaultOptions = array(
                    'choices' => array(
                        'null' => 'datasource.form.choices.isnull',
                        'notnull' => 'datasource.form.choices.isnotnull'
                    ),
                    'multiple' => false,
                    'empty_value' => '',
                    'translation_domain' => 'DataSourceBundle'
                );

                if (isset($options['choices'])) {
                    $options['choices'] = array_merge(
                        $defaultOptions['choices'],
                        array_intersect_key($options['choices'], $defaultOptions['choices'])
                    );
                }

                $options = array_merge($defaultOptions, $options);

                $builder->add($field->getName(), 'choice', $options);

                break;

            default:
                $type = $field->hasOption('form_type')?$field->getOption('form_type'):$field->getType();
                $builder->add($field->getName(), $type, $options);
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
