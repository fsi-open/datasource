# Symfony Core Extension #

Main and only purpose of this extension is to convert Symfony's Request object into array.
What's important it **must** be loaded as first extension, so can convert it before any other extension
will try to access it (and throw Exception probably).

It extends **datasource**.

## Requirements ##

Symfony Http Foundation ("symfony/http-foundation")

## Setup ##

Just add it to extensions while creating new DataSouce.

``` php
<?php

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Extension\Symfony\Core\CoreExtension;

$extensions = array(
    new CoreExtension(),
    //(...) Other extensions.
);

$factory = new DataSourceFactory($extensions);

```