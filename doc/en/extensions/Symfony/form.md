# Symfony Form Extension #

This extension builds forms for datasource fields so use them to easly create "filtes" on web pages.
It loads event subscriber to **fields**.

## Requirements ##

Symfony Form component ("symfony/form")

## Setup ##

Add its instance as extension while creating new DataSource or DataSourceFactory. It requires fully configured
``Symfony\Component\Form\FormFactory`` as an argument to the constructor.

``` php
<?php

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Extension\Symfony\Form\FormExtension;

$formFactory; // Preconfigured instance of Symfony\Component\Form\FormFactory

$extensions = array(
    new FormExtension($formFactory),
    // (...) Other extensions.
);

$factory = new DataSourceFactory($extensions);

```

## Extended field types ##

``text``, ``number``, ``date``, ``time``, ``datetime``, ``entity``, ``boolean``

## Available field options ##

* ``form_filter`` - whether form creation and rendering for this field is enabled, ``true`` by default.
* ``form_order`` - optional integer value specifying order of fields in filter form; fields in filter form are sorted according
  to ascending value of this option; field that has not this option set will stay in their natural order (between fields with
  positive and negative values of this option).
* ``form_type`` - type of form that should be created for this datasource field, by default it equals to the type of datasource
  field, it can be any valid form type.
* ``form_options`` - array of options passed to the form field (see documentation for Symfony Form component for details)
* ``form_from_options`` - optional array of options passed to the ``from`` form field in datasource fields with ``between``
  comparison; it's merged with ``form_options``.
* ``form_to_options`` - optional array of options passed to the ``to`` form field in datasource fields with ``between``
  comparison; it's merged with ``form_options``.

**Note**: Remember that for fields of type ``entity`` you **must** always pass ``class`` option in ``form_option`` in order to
specify class of associated entity. Otherwise an exception will be thrown from Symfony Form component.

``` php
<?php

$datasource
    ->addField('group', 'entity', 'memberof', array(
        'form_options' => array(
            'class' => 'Name\Of\Group',
        ),
    ))
;

```
 
## FieldView attributes ##

* ``form`` - set on each field's view (unless it has ``form_filter`` option set to ``false``), if set it's an instance of
  ``Symfony\Component\Form\FormView``
