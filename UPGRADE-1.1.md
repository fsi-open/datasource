UPGRADE FROM 1.0.x to 1.1.0
============================

## Field`s

Added ``boolean`` field type to collection driver ``FSi\Component\DataSource\Driver\Collection\Extension\Core\Field\Boolean`` and doctrine driver ``FSi\Component\DataSource\Driver\Doctrine\Extension\Core\Field\Boolean``.

## Symfony Form Field Extension

Added new field type support: ``boolean``

Added new options:
```php
...
'form_null_value' => 'empty', // string
'form_not_null_value' => 'not empty', // string
'form_true_value' => 'yes', // string
'form_false_value' => 'no', // string
'form_translation_domain' => 'DataSourceBundle', // null or string
...
```

**NB!!! form_translation_domain default value ``DataSourceBundle`` will be changed to ``null`` in version 1.2**

## New method ``getDBALType()`` in DoctrineFieldInterface

New method was added in order to allow type casting in DQL queries. Field implementation can return null or one of class
constants from Doctrine\DBAL\Types\Type. If null is returned, which is the default behaviour of DoctrineAbstractField,
then field's value will not be casted in DQL.

## New method ``getPHPType()`` in CollectionFieldInterface

New method was added in order to allow type casting before comparison. Field implementation can return null or one of types
accepted by ``settype()``. If null is returned, which is the default behaviour of CollectionAbstractField, then field's
value will not be casted before comparison.
