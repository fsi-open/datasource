UPGRADE FROM 0.9.x to 1.0.0
============================

## Drivers

Added ``FSi\Component\DataSource\Driver\DriverFactoryInterface`` interface.


Changed arguments in methods:
* ``FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory::createDriver()``
* ``FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory::createDataSource()``
* ``FSi\Component\DataSource\Driver\Collection\CollectionDriver::createDriver()``
* ``FSi\Component\DataSource\Driver\Collection\CollectionDriver::createDataSource()``

Before:

```php
/* @var $factory \FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory */
$driver = $factory->createDriver($entity, $alias, $entityManager);

$driver = $factory->createDataSource($entity, $name, $alias, $entityManager);
```

After:

```php
/* @var $factory \FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory */
$driver = $factory->createDriver(array(
    'entity' => $entity, // string|QueryBuilder
    'alias' => $alias, // string
    'em' =>  $entityManager // string
));

$driver = $factory->createDataSource(array(
    'entity' => $entity, // string|QueryBuilder
    'name' => $name, // string
    'alias' => $alias, // string
    'em' =>  $entityManager // string
));
```

Before:

```php
/* @var $factory \FSi\Component\DataSource\Driver\Collection\CollectionDriver */
$driver = $factory->createDriver(array $collection);

$driver = $factory->createDataSource(array $collection, $name = 'datasource');
```

After:

```php
/* @var $factory \FSi\Component\DataSource\Driver\Collection\CollectionDriver */
$driver = $factory->createDriver(array(
    'collection' => $collection // array
));

$driver = $factory->createDataSource(array(
    'collection' => $collection, // array
    'name' => $name // string
));
```