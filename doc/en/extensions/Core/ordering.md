# Core Ordering Extension #

``OrderingExtension`` allows to set default sorting criteria through fields' options, processes user sorting criteria passed in
input parameters and combines these two and tells driver to sort results in specified way.

It loads event subscribers to **datasource**, **fields** and **driver**.

## Requirements ##

None.

## Setup ##

Just add it to extensions while creating new DataSouce or DataSourceFactory.

``` php
<?php

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Extension\Core\OrderingExtension;

$extensions = array(
    new OrderingExtension(),
    // (...) Other extensions.
);

$factory = new DataSourceFactory($extensions);

```

## Extended field types ##

``text``, ``number``, ``date``, ``time``, ``datetime``

## Available field options ##

* ``ordering_disabled`` - if true, field won't get any ordering parameters, ``false`` by default
* ``ordering`` - default ordering ('asc' or 'desc'), note: it works **even if** ``ordering_disabled`` is set to ``true``
* ``ordering_priority`` - default ordering priority, note: it works **even if** ``ordering_disabled`` is set to ``true``

## Parameters ##

OrderingExtension reads input parameters from 'ordering' key. It must be an array where keys are field names and values determine
direction of sorting results by these fields i.e. Order of fields in this array determine order of sorting.

``` php
<?php

$parameters = array(
    'datasource' => array(
        'ordering' => array(
            'field1' => 'asc',
            'field2' => 'desc
        )
    )
);
```

In this example results will be sorted ascending by value of field1 and then descending by value of field2

## FieldView attributes ##

* ``ordering_disabled`` - if true, field should not display sorting anchors, ``false`` by default
* ``ordering_current`` - ``'asc'`` or ``'desc'`` if this field is current primary sorting field or ``''`` otherwise
* ``ordering_ascending`` - array of parameters that should be passed to the URL which will sort current results by value of this field in an ascending order
* ``ordering_descending`` - array of parameters that should be passed to the URL which will sort current results by value of this field in a descending order

## example ##

This example shows how default sorting criteria are combined with sorting parameters passed by the user.

``` php
<?php

$datasource
    ->addField('id', 'number', 'eq')
    ->addField('title', 'text', 'like', array(
        'ordering' => 'asc',
        'ordering_priority' => 1
    ))
    ->addField('author', 'text', 'eq')
    ->addField('create_date', 'datetime', 'between', array(
        'ordering' => 'desc',
        'ordering_priority' => 2
    ))
    ->addField('content', 'text', 'like')
;

```

Using such a datasource definition would return results sorted by:
* ``create_date`` descending
* ``title`` ascending

Now if we bind parameters like these...

``` php
<?php

$parameters = array(
    'datasource' => array(
        'ordering' => array(
            'author' => 'asc',
            'title' => 'asc
        )
    )
)

```

... the results will be sorted by:

* ``author`` ascending
* ``title`` ascending
* ``create_date`` descending

As you can see sorting criteria passed by the user are always more important than default criteria set through fields' options.

