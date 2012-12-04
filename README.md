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
use FSi\Component\DataSource\DataSourceFactory;

$extensions = array(
    //(...) Extensions that have to be loaded to DataSource after creation.
);

$factory = new DataSourceFactory($extensions);

$datasource = $factory->createDataSource($driver, 'datasource_name');
```

Then, if we want to give some conditions for returned data we need to specify fields, their type and way of comparison.
``` php
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
$datasource->setMaxResults(20);
$datasource->setFirstResult(0);
```

And at last we can fetch our data
``` php
$result = $datasource->getResult();
```

or create view helpfull during view rendering.
``` php
$view = $datasource->createView();
```

Result that is returned always impements ``Traversable`` interface.

Note, that in fact all you need to do to fetch data is create DataSource and call ``getResult`` method, other steps are optional.
