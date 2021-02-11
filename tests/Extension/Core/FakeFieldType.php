<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Tests\Extension\Core;

use FSi\Component\DataSource\Field\FieldAbstractType;

class FakeFieldType extends FieldAbstractType
{
    public function getType(): string
    {
        return 'fake';
    }
}
