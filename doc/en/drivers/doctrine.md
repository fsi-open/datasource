# Doctrine Driver #

This driver allows to fetch data from database using Doctrine2 ORM. 

## Setup ##

You can create driver manually

``` php
<?php

use FSi\Component\DataSource\Driver\Doctrine\DoctrineDriver;
use FSi\Component\DataSource\Driver\Doctrine\Extension\Core\CoreExtension;

$driverExtensions = array(new CoreExtension());

$driver = new DoctrineDriver($driverExtensions, $entityManager, $entityName);

```

or through factory

``` php
<?php

use FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory;
use FSi\Component\DataSource\Driver\Doctrine\Extension\Core\CoreExtension;
use FSi\Component\DataSource\DataSourceFactory;

$extensions = array(
    // (...) Extensions that have to be loaded to every DataSource after creation.
);

$factory = new DataSourceFactory($extensions);

$driverExtensions = array(new CoreExtension());

$driverFactory = new DoctrineFactory($ManagerRegistry, $factory, $driverExtensions);
$driver = $driverFactory->createDriver($entityName); // All drivers created this way will have same set of $driverExtensions loaded.

```

You can also create dataosurce directly from driver factory

``` php
<?php

use FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory;
use FSi\Component\DataSource\Driver\Doctrine\Extension\Core\CoreExtension;
use FSi\Component\DataSource\DataSourceFactory;

$extensions = array(
    // (...) Extensions that have to be loaded to every DataSource after creation.
);

$factory = new DataSourceFactory($extensions);

$driverExtensions = array(new CoreExtension());

$driverFactory = new DoctrineFactory($ManagerRegistry, $factory, $driverExtensions);
$datasource = $driverFactory->createDataSource($entityName, $dataSourceName); // All drivers created this way will have same set of $driverExtensions loaded.

```

## Provided fields ##

Doctrine driver provides some field types through ``FSi\Component\DataSource\Driver\Doctrine\Extension\Core\CoreExtension``
so remember to **always load it** to this driver.

Provided field types:

* ``text`` - allowed comparisons: eq, neq, in, notIn, like, contains.
* ``number`` - allowed comparisons: eq, neq, lt, lte, gt, gte, in, notIn, between.
* ``date`` - allowed comparisons: eq, neq, lt, lte, gt, gte, in, notIn, between.
* ``time`` - allowed comparisons: eq, neq, lt, lte, gt, gte, in, notIn, between.
* ``datetime`` - allowed comparisons: eq, neq, lt, lte, gt, gte, in, notIn, between.
* ``entity`` - allowed comparisons: eq, memberof, in.

Note: When using ``between`` comparison, you must bind parameters as array('from' => $value1, 'to' => $value2), 
if ``entity`` you must give entity to it and if ``in``, or ``notIn`` then as array.

All fields allow by default to set option ``field`` which usage is explained below.

## Basic usage ##

In the simpliest case you must just create driver with proper entity name and use it to create DataSource:

``` php
<?php

$driverFactory = new DoctrineFactory($entityManager, $driverExtensions);
$driver = $driverFactory->createDriver('Name\Of\Entity'); // It can be any entity name that is known to Doctrine.

$datasourceFactory = new DataSourceFactory($datasourceExtensions);
$datasource = $datasourceFactory->createDataSource($driver, 'datasource_name');

$datasource
    ->addField('id', 'number', 'eq')
    ->addField('title', 'text', 'like')
    ->addField('author', 'text', 'eq')
    ->addField('create_date', 'datetime', 'between')
    ->addField('content', 'text', 'like')
    ->addField('category', 'entity', 'eq')
    ->addField('group', 'entity', 'memberof')
;
```

You can use ``field`` option to have different field name, or many DataSource fields referring to one entity field:

``` php
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

## Using predefined QueryBuilder ##

You can also use predefined QueryBuilder, and if so, you can pass it to factory or DoctrineDriver insetad of ``$entityName``.
If you do you can also pass an alias of entity as additional argument.

``` php
<?php

$queryBuilder = $entityManager->createQueryBuilder();
$queryBuilder
    ->select('n')
    ->from('Name\Of\Entity', 'n')
    ->where('n.active = 1') // All results will have additional condition.
;

// Factory way:

$factory = new DoctrineFactory($entityManager, $extensions);
$driver = $factory->createDriver($queryBuilder, 'n'); // Passing alias which otherwise would be guessed from root entity of $queryBuilder.

// Manual way:

$driver = new DoctrineDriver($extensions, $entityManager, $queryBuilder, 'n'); // Passing alias which otherwise would be guessed from root entity of $queryBuilder.
```

## Advanced use with QueryBuilder ##

If you want to have conditions to fields from joined entities, or you build very sophisticated query,
remember to add field mapping to all of fields, otherwise they will try do refer to root entity alias.

``` php
<?php

$queryBuilder = $entityManager->createQueryBuilder();
$queryBuilder
    ->select('n')
    ->from('Name\Of\Entity', 'n')
    ->join('n.category', 'c') // Joining category.
    ->where('n.active = 1')
;
$factory = new DoctrineFactory($entityManager, $extensions);
$driver = $factory->createDriver($queryBuilder); // We don't need to pass alias, if we specify field mappings.

$datasource
    ->addField('id', 'number', 'eq', array('field' => 'n.id'))
    ->addField('title', 'text', 'like', array('field' => 'n.title'))
    ->addField('category_name', 'text', 'like', array( // It's not entity field anymore.
        'field' => 'c.name', // It allow us to specify condition for category name, not just category (as entity).
    ))
;

```
