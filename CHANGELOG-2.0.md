# Changelog for version 2.0

This is a list of changes done in version 2.0.

## Collection driver enhancements

Added support of:
* `\Doctrine\Common\Collections\Selectable`
* `\Traversable`

to collection driver. Read more about it in [Collection driver documentation](doc/en/drivers/collection.md).

## Deleted Symfony extension

Since it was moved to [datasource-bundle](https://github.com/fsi-open/datasource-bundle),
it has been removed from this component.

## Dropped support for PHP below 7.1

To be able to fully utilize new functionality introduced in 7.1, we have decided
to only support PHP versions equal or higher to it.

## Deleted deprecated classes

Following classes have been deleted due to being deprecated in branch 1.x:

<table>
    <thead>
        <th>Class name</th>
    </thead>
    <tbody>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\DoctrineDriver</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\DoctrineFactory</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\DoctrineFieldInterface</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\DoctrineResult</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Exception\DoctrineDriverException</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Extension\Core\CoreExtension</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Extension\Core\EventSubscriber\ResultIndexer</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Extension\Core\Field\Boolean</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Extension\Core\FieldDate</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Extension\Core\FieldDateTime</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Extension\Core\FieldEntity</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Extension\Core\FieldNumber</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Extension\Core\FieldText</td>
        </tr>
        <tr>
            <td>FSi\Component\DataSource\Driver\Doctrine\Extension\Core\FieldTime</td>
        </tr>
    </tbody>
</table>

## Text field no longer allows "Not In" comparison

Specifying `'nin'` comparison for `text` field is no longer viable.
