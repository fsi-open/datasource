# FSi DataSource Component #

DataSource allows to fetch data from various sources using appropriate driver.
It supports pagination and trough fields (that driver or its extensions must provide) allows
to give various conditions, that fetched data must fulfill.

DataSource to fetch specific kind of data (from database, xml, json, etc.) must be created with
properly configured driver, that will implement methods to get that kind of data.

## Basic usage ##

Lets assume that you have already preconfigured ``$driver`` (you can find documentation for specific drivers
in doc folder) that allows us to fetch some data, which can be seen as array objects with specific fields - for our
example let it be News objects with id, title, author, create date, short content and content.

First we must create DataSource with proper ``$driver``.
``` php
<?php

use FSi\Component\DataSource\DataSourceFactory;

$extensions = array(
    //(...) Extensions that have to be loaded to DataSource after creation.
);

$factory = new DataSourceFactory($extensions);

$datasource = $factory->createDataSource($driver, 'datasource_name');
```

Then, if we want to give some conditions for returned data we need to specify fields, their type and way of comparison.
``` php
<?php

$datasource
    ->addField('id', 'number', 'eq')
    ->addField('title', 'text', 'like')
    ->addField('author', 'text', 'eq')
    ->addField('create_date', 'datetime', 'between', array(
        'someoption' => 'somevalue', //Specific options that this field allows.
    ))
    ->addField('content', 'text', 'like')
;
```

If we have configured DataSource we can bind it some parameters for fields.
``` php
<?php

$parameters = array(
    'datasource_name' => array(
        'fields' => array( //In fact this key if always equal to constant \FSi\Component\DataSource\DataSourceInterface::FIELDS.
            'title' => 'part of searched title',
            'author' => 'author@example.com',
            'create_date' => array( //Input data doesn't have to be scalar, but it must in form that fields expects it.
                'from' => '2012-04-04',
                'to' => '2012-12-12',
            ),
        ),
    ),
);

$datasource->bindParameters($parameters);
```

We can also set proper pagination.
``` php
<?php

$datasource->setMaxResults(20);
$datasource->setFirstResult(0);
```

And at last we can fetch our data
``` php
<?php

$result = $datasource->getResult();
```

or create view helpfull during view rendering.
``` php
<?php

$view = $datasource->createView();
```

Result that is returned always impements ``Traversable`` interface.

Note, that in fact all you need to do to fetch data is create DataSource and call ``getResult`` method, other steps are optional.

## Extensions ##

**You can find available extensions documentation in doc folder.**

In general there are three types of extensions: extensions for DataSource, driver, and field.

All parts of this component use Symfony's EventDispatcher (``Symfony\Component\EventDispatcher\EventDispatcher``) to manage events.

### Extensions for DataSource ###

Each extension must implement interface ``FSi\Component\DataSource\DataSourceExtensionInterface``.
Method ``loadSubscribers`` must return array of objects that (if any) must implement ``Symfony\Component\EventDispatcher\EventSubscriberInterface``.
Method ``loadDriverExtensions`` must return array of objects that (if any) must be valid driver extensions (see below).

Each of subscribers can subscribe to one of following events:
(next to event names listed what kind of argument is passed to that subscribed objects method)

* ``FSi\Component\DataSource\Event\DataSourceEvents::PRE_BIND_PARAMETERS``: ``FSi\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs``
* ``FSi\Component\DataSource\Event\DataSourceEvents::POST_BIND_PARAMETERS``: ``FSi\Component\DataSource\Event\DataSourceEvent\DataSourceEventArgs``
* ``FSi\Component\DataSource\Event\DataSourceEvents::PRE_GET_RESULT``: ``FSi\Component\DataSource\Event\DataSourceEvent\DataSourceEventArgs``
* ``FSi\Component\DataSource\Event\DataSourceEvents::POST_GET_RESULT``: ``FSi\Component\DataSource\Event\DataSourceEvent\ResultEventArgs``
* ``FSi\Component\DataSource\Event\DataSourceEvents::PRE_BUILD_VIEW``: ``FSi\Component\DataSource\Event\DataSourceEvent\ViewEventArgs``
* ``FSi\Component\DataSource\Event\DataSourceEvents::POST_BUILD_VIEW``: ``FSi\Component\DataSource\Event\DataSourceEvent\ViewEventArgs``
* ``FSi\Component\DataSource\Event\DataSourceEvents::PRE_GET_PARAMETERS``: ``FSi\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs``
* ``FSi\Component\DataSource\Event\DataSourceEvents::POST_GET_PARAMETERS``: ``FSi\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs``

### Extension for Driver ###

Each extension must implement interface ``FSi\Component\DataSource\Driver\DriverExtensionInterface``.
Method ``loadSubscribers`` must return array of objects that (if any) must implement ``Symfony\Component\EventDispatcher\EventSubscriberInterface``.

Drivers extensions provides field types through methods ``hasFieldType`` and ``getFieldType``,
where getFieldType must return field object for given type, that implements ``FSi\Component\DataSource\Field\FieldTypeInterface``
and already has all its extensions loaded.

Each of subscribers can subscribe to one of following events:
(next to event names listed what kind of argument is passed to that subscribed objects method)

* ``FSi\Component\DataSource\Event\DriverEvents::PRE_GET_RESULT``: ``FSi\Component\DataSource\Event\DriverEvent\DriverEventArgs``
* ``FSi\Component\DataSource\Event\DriverEvents::POST_GET_RESULT``: ``FSi\Component\DataSource\Event\DriverEvent\ResultEventArgs``

### Extension for Field ###

Each extension must implement interface ``FSi\Component\DataSource\Field\FieldExtensionInterface``
Method ``loadSubscribers`` must return array of objects that (if any) must implement ``Symfony\Component\EventDispatcher\EventSubscriberInterface``.

Each of subscribers can subscribe to one of following events:
(next to event names listed what kind of argument is passed to that subscribed objects method)

* ``FSi\Component\DataSource\Event\FieldEvents::PRE_BIND_PARAMETER``: ``FSi\Component\DataSource\Event\FieldEvent\ParameterEventArgs``
* ``FSi\Component\DataSource\Event\FieldEvents::POST_BIND_PARAMETER``: ``FSi\Component\DataSource\Event\FieldEvent\FieldEventArgs``
* ``FSi\Component\DataSource\Event\FieldEvents::PRE_BUILD_VIEW``: ``FSi\Component\DataSource\Event\FieldEvent\ViewEventArgs``
* ``FSi\Component\DataSource\Event\FieldEvents::POST_BUILD_VIEW``: ``FSi\Component\DataSource\Event\FieldEvent\ViewEventArgs``
* ``FSi\Component\DataSource\Event\FieldEvents::PRE_GET_PARAMETER``: ``FSi\Component\DataSource\Event\FieldEvent\ParameterEventArgs``
* ``FSi\Component\DataSource\Event\FieldEvents::POST_GET_PARAMETER``: ``FSi\Component\DataSource\Event\FieldEvent\ParameterEventArgs``
