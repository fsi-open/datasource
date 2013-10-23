UPGRADE FROM 0.9.x to 1.0.0
============================

## Drivers

Added ``FSi\Component\DataSource\Driver\DriverFactoryInterface`` interface.


Changed arguments in methods:
* ``FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory::createDriver()``
* ``FSi\Component\DataSource\Driver\Collection\CollectionDriver::createDriver()``

Removed methods:
* ``FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory::createDataSource()``
* ``FSi\Component\DataSource\Driver\Collection\CollectionDriver::createDataSource()``

Before:

```php
/* @var $factory \FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory */
$driver = $factory->createDriver($entity, $alias, $entityManager);
```

After:

```php
/* @var $factory \FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory */
$driver = $factory->createDriver(array(
    'entity' => $entity, // string|null
    'qb' => $qb, // QueryBuilder|null
    'alias' => $alias, // string
    'em' =>  $entityManager // string
));
```

Before:

```php
/* @var $factory \FSi\Component\DataSource\Driver\Collection\CollectionDriver */
$driver = $factory->createDriver(array $collection);
```

After:

```php
/* @var $factory \FSi\Component\DataSource\Driver\Collection\CollectionDriver */
$driver = $factory->createDriver(array(
    'collection' => $collection // array
));
```

## DataSource Factory

Changed method ``createDataSource``

Before:

```php
/**
 * Creates instance of data source with given driver and name.
 *
 * @param \Driver\DriverInterface $driver
 * @param string $name
 * @return DataSource
 */
public function createDataSource(Driver\DriverInterface $driver, $name);
```

After:

```php
/**
 * Creates instance of data source with given driver and name.
 *
 * @param $driver string
 * @param array $driverOptions
 * @param $name
 * @return mixed
 */
public function createDataSource($driver, $driverOptions = array(), $name);
```
