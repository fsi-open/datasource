# Symfony Core Extension #

Main and only purpose of this extension is to convert Symfony's Request object into an array of parameters.
It loads event subscriber to **datasource** with highest priority to transform request before any other extension can access it.

## Requirements ##

Symfony Http Foundation component ("symfony/http-foundation")

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
