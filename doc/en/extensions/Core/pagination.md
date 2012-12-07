# Core Pagination Extension #

Allows to render pagination by setting some view attributes.

It extends **datasource**.

## Requirements ##

None.

## Setup ##

Just add it to extensions while creating new DataSouce.

``` php
<?php

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Extension\Core\PaginationExtension;

$extensions = array(
    new PaginationExtension(),
    //(...) Other extensions.
);

$factory = new DataSourceFactory($extensions);

```

## View attributes ##

* ``page_param_name`` - key to pass page number in get request
* ``page_current`` - current page
* ``page_amount`` - amount of all pages