# Collection Driver #

This driver allows DataSource to operate on data objects contained in:
* plain array `[]`
* [\Traversable](http://php.net/manual/en/class.traversable.php) object
* [\Doctrine\Common\Collections\Selectable](https://github.com/doctrine/collections/blob/ab12b39de988ee3d80bf2d5f16b1161ee5fb2529/lib/Doctrine/Common/Collections/Selectable.php) object

The purpose of this driver is to use DataSource's abilities (filtering, sorting, pagination) on data coming
from source that natively doesn't support such operations.

## Setup ##

You can create driver manually from plain array of objects:

```php
<?php

use Doctrine\Common\Collections\Criteria;
use FSi\Component\DataSource\Driver\Collection\CollectionDriver;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\CoreExtension;

$driverExtensions = array(new CoreExtension());
$baseCriteria = Criteria::create()->orderBy(['name' => Criteria::ASC]);

$driver = new CollectionDriver($driverExtensions, $arrayOfObjects, $baseCriteria);
```

or from `\Doctrine\Common\Collections\Selectable` object:

```php
$driver = new CollectionDriver($driverExtensions, $this->em->getRepository(News::class), $baseCriteria);
```

You can also use factory to create driver:

```php
<?php

use Doctrine\Common\Collections\Criteria;
use FSi\Component\DataSource\Driver\Collection\CollectionFactory;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\CoreExtension;

$extensions = array(
    // (...) Extensions that have to be loaded to every DataSource after creation.
);

$driverExtensions = array(new CoreExtension());

$driverFactory = new CollectionFactory($driverExtensions);
$baseCriteria = Criteria::create()->orderBy(['name' => Criteria::ASC]);
$driver = $driverFactory->createDriver(['collection' => $arrayOfObjects, 'criteria' => $baseCriteria]); // All drivers created this way will have same set of $driverExtensions loaded.

```

## Provided fields ##

Doctrine driver provides some field types through ``FSi\Component\DataSource\Driver\Doctrine\Extension\Core\CoreExtension``
so remember to **always load it** to this driver.

Provided field types:

* ``text`` - allowed comparisons: eq, neq, in, nin (not in), contains.
* ``number`` - allowed comparisons: eq, neq, lt, lte, gt, gte, in, nin (not in), between.
* ``date`` - allowed comparisons: eq, neq, lt, lte, gt, gte, in, nin (not in), between.
* ``time`` - allowed comparisons: eq, neq, lt, lte, gt, gte, in, nin (not in), between.
* ``datetime`` - allowed comparisons: eq, neq, lt, lte, gt, gte, in, nin (not in), between.
* ``boolean`` - allowed comparisons: eq.

Note: When using ``between`` comparison, you must bind parameters as array('from' => $value1, 'to' => $value2),

All fields allow by default to set option ``field`` which usage is explained below.

## Basic usage ##

In the simpliest case you must just create driver with proper array of objects and use it to create DataSource. In this example
$data array is constructed from simple objects but it can contain any objects with consistent fields among the whole array.

```php
<?php

$data = array(
    new ExampleObject('field1', 'field2', 'field2'),
    new ExampleObject('field1', 'field2', 'field2'),
    ...
);

$driverManager = DriverFactoryManager(array(
    new DoctrineFactory($driverExtensions)
));

$datasourceFactory = new DataSourceFactory($driverManager, $datasourceExtensions);
$driverOptions = array(
    'collection' => $data
);
$datasource = $datasourceFactory->createDataSource('collection', $driverOptions, 'datasource_name');

$datasource->addField('id', 'number', 'eq')
    ->addField('title', 'text', 'eq')
    ->addField('author', 'text', 'eq')
    ->addField('create_date', 'datetime', 'between');
```

You can use ``field`` option to have different field name, or many DataSource fields referring to one object field:

```php
<?php

$datasource
    ->addField('veryweirdname' 'number', 'eq', array(
        'field' => 'id',
    ))
    ->addField('datefrom', 'datetime', 'gte', array(
        'field' => 'create_date',
    ))
    ->addField('dateto', 'datetime', 'lte', array(
        'field' => 'create_date',
    ))
;
```
