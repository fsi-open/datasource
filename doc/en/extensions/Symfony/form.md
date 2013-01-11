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

``text``, ``number``, ``date``, ``time``, ``datetime``, ``entity``

## Available field options ##

* ``form_disabled`` - whether form creation and rendering for this field is disabled, ``false`` by default
* ``form_options`` - options passed to the form field (see documentation for Symfony Form component for details); for ``between``
  comparison, you can pass specific options for each field by passing two arrays in this option (extension will also search for
  specific options under ``from`` and ``to`` keys); If you pass also some general options, they will be merged to specific options,
  but specific options have higher precedence.

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

* ``form`` - set on each field's view (unless it has ``form_disabled`` option set to true), if set it's an instance of
  ``Symfony\Component\Form\FormView``
