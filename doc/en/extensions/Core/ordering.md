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

* ``sortable`` - if false, field won't get any sorting parameters, ``true`` by default
* ``default_sort`` - default sorting direction ('asc' or 'desc'), note: it works **even if** ``sortable`` is set to ``false``
* ``default_sort_priority`` - default sorting priority, note: it works **even if** ``sortable`` is set to ``false``

## Parameters ##

OrderingExtension reads input parameters from 'ordering' key. It must be an array where keys are field names and values determine
direction of sorting results by these fields i.e. Order of fields in this array determine order of sorting.

``` php
<?php

$parameters = array(
    'datasource' => array(
        'sort' => array(
            'field1' => 'asc',
            'field2' => 'desc
        )
    )
);
```

In this example results will be sorted ascending by value of field1 and then descending by value of field2

## FieldView attributes ##

* ``sortable`` - if ``false``, field should not display sorting anchors, ``true`` by default
* ``sorted_ascending`` - ``true`` if this field is currently primary sorting field in ascending order or ``false`` otherwise
* ``sorted_descending`` - ``true`` if this field is currently primary sorting field in descending order or ``false`` otherwise
* ``parameters_sort_ascending`` - array of parameters that should be passed to the URL which will sort current results by value of this field in an ascending order
* ``parameters_sort_descending`` - array of parameters that should be passed to the URL which will sort current results by value of this field in a descending order

## example ##

This example shows how default sorting criteria are combined with sorting parameters passed by the user.

``` php
<?php

$datasource
    ->addField('id', 'number', 'eq')
    ->addField('title', 'text', 'like', array(
        'default_sort' => 'asc',
        'default_sort_priority' => 1
    ))
    ->addField('author', 'text', 'eq')
    ->addField('create_date', 'datetime', 'between', array(
        'default_sort' => 'desc',
        'default_sort_priority' => 2
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
        'sort' => array(
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

