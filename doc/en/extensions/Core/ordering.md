# Core Ordering Extension #

Ordering allows to set to fields what kind of ordering they should define, and with what priority.

It extends **datasource** and loads extensions for **fields**.

## Requirements ##

None.

## Setup ##

Just add it to extensions while creating new DataSouce.

``` php
<?php

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Extension\Core\OrderingExtension;

$extensions = array(
    new OrderingExtension(),
    //(...) Other extensions.
);

$factory = new DataSourceFactory($extensions);

```

## Extended field types ##

``text``, ``number``, ``date``, ``time``, ``datetime``, ``entity``

## Available field options ##

* ``ordering_given`` - for internal use only, you shoudn't make any use of it
** ``false`` by default
* ``ordering_disabled`` - if true, field won't get any ordering parameters
** ``false`` by default
* ``ordering`` - default ordering (i.e. 'asc'), note: it works **even if** ``ordering_disabled`` is set to ``true``
* ``ordering_priority`` - default ordering priority, note: it works **even if** ``ordering_disabled`` is set to ``true``

## Field options ##

In ``FSi\Component\DataSource\Event\DataSourceEvents::PRE_GET_RESULT`` event extension sets proper options for fields:
``ordering`` and ``ordering_priority`` and **it's up to driver** to 'catch' these options and make use of them.

## View attributes ##

(Note: Whenever 'key' is mentioned below - it's GET key, that allows us to send some data in form ``key=value``.)

* ``ordering_next_priority`` - next priority that should be given for next criterion
* ``ordering_pattern`` - use ``sprintf($ordering_pattern, $field->getName()`` to obtain key for next ordering
* ``ordering_priority_pattern`` - use ``sprintf($ordering_priority_pattern, $field->getName()`` to obtain key for next ordering priority
* ``resetpage`` - key that has to be set to true to reset DataSource page to first one. (It's usefull when determining new ordering.)

## FieldView attributes ##

* ``ordering_disabled`` - if true, field doesn't react for given parameters
** ``false`` by default
* ``ordering_current`` - determines if there is ``asc`` or ``desc`` ordering set (if any)
* ``ordering_current_priority`` - determines this field current ordering priority
* ``ordering_enabled`` - ``true`` if there is any priority or ordering given by client (even if values are same as default ones)
