# Symfony Form Extension #

Builds forms for fields and binds them as attributes to fields views.
It also automatically maps data between form and datasources fields.

It loads extensions for **fields**.

## Requirements ##

Symfony Form ("symfony/form")

## Setup ##

Add its instance as extension while creating new DataSource. It requires fully configured ``Symfony\Component\Form\FormFactory`` in constructor. 

``` php
<?php

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Extension\Symfony\Form\FormExtension;

$formFactory; //Preconfigured instance of Symfony\Component\Form\FormFactory

$extensions = array(
    new FormExtension($formFactory),
    //(...) Other extensions.
);

$factory = new DataSourceFactory($extensions);

```

## Extended field types ##

``text``, ``number``, ``date``, ``time``, ``datetime``, ``entity``

## Available field options ##

* all fields
    * ``form_disabled`` - whether form rendering for this field is disabled
        * ``false`` by default
    * ``form_label`` - label for form field(s)
    * ``form_options`` - options passed to form (see documentation for Symfony Form fields)
 
## FieldView attributes ##

* ``form`` - set on each fields view (unless it has ``form_disabled`` option set to true)
    * if set, instance of ``Symfony\Component\Form\FormView``

## Entity field ##

**Note:** Remember you **must** pass ``class`` option in ``form_options`` with proper entity name to render form correctly.
Otherwise exception will be thrown.

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
