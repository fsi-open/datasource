<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Fixtures;

use ArrayIterator;

/**
 * Class for testing result returning.
 */
class TestResult implements \Countable, \IteratorAggregate
{
    public function count()
    {
        return 0;
    }

    public function getIterator()
    {
        return new ArrayIterator([]);
    }
}
