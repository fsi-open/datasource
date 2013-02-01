# Core Pagination Extension #

Allows pagination of results from datasource. It loads event subscriber only to **datasource**.

## Requirements ##

None.

## Setup ##

Just add it to extensions while creating new DataSouce or DataSourceFactory.

``` php
<?php

use FSi\Component\DataSource\DataSourceFactory;
use FSi\Component\DataSource\Extension\Core\PaginationExtension;

$extensions = array(
    new PaginationExtension(),
    // (...) Other extensions.
);

$factory = new DataSourceFactory($extensions);

```

## Parameters ##

PaginationExtension reads only 'page' parameter from which it calculates number of first result to get from driver.

## View attributes ##

* ``page`` - current page number
* ``parameters_pages`` - array of sets of parameters for generating URL to every page of current results.
