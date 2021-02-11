<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Field;

use FSi\Component\DataSource\Field\FieldView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\DataSourceView;

class FieldViewTest extends TestCase
{
    /**
     * Checks creation.
     */
    public function testCreate(): void
    {
        /** @var FieldTypeInterface|MockObject $field */
        $field = $this->createMock(FieldTypeInterface::class);

        $field->expects(self::atLeastOnce())->method('getName')->willReturn('somename');
        $field->expects(self::atLeastOnce())->method('getType')->willReturn('sometype');
        $field->expects(self::atLeastOnce())->method('getComparison')->willReturn('somecomp');
        $field->expects(self::atLeastOnce())->method('getCleanParameter')->willReturn('someparam');

        $fieldView = new FieldView($field);

        self::assertEquals($field->getName(), $fieldView->getName());
        self::assertEquals($field->getType(), $fieldView->getType());
        self::assertEquals($field->getComparison(), $fieldView->getComparison());
        self::assertEquals($field->getCleanParameter(), $fieldView->getParameter());
    }

    /**
     * Checks correctness of reference to DataSourceView.
     */
    public function testSetDataSourceView(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $datasource = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();
        $view = $this->getMockBuilder(DataSourceView::class)->setConstructorArgs([$datasource])->getMock();
        $field = $this->createMock(FieldTypeInterface::class);
        $fieldView = new FieldView($field);

        $fieldView->setDataSourceView($view);
        self::assertEquals($fieldView->getDataSourceView(), $view);
    }

    /**
     * Checks the correctness of options related methods.
     */
    public function testOptionsManipulation(): void
    {
        $field = $this->createMock(FieldTypeInterface::class);
        $view = new FieldView($field);

        self::assertFalse($view->hasAttribute('option1'));
        $view->setAttribute('option1', 'value1');
        self::assertTrue($view->hasAttribute('option1'));
        self::assertEquals('value1', $view->getAttribute('option1'));
        $view->removeAttribute('option1');
        self::assertFalse($view->hasAttribute('option1'));

        $view->setAttribute('option2', '');
        self::assertTrue($view->hasAttribute('option2'));

        $view->setAttribute('option2', null);
        self::assertTrue($view->hasAttribute('option2'));

        $view->setAttribute('option3', 'value3');
        $view->setAttribute('option4', 'value4');

        self::assertEquals(['option2' => null, 'option3' => 'value3', 'option4' => 'value4'], $view->getAttributes());

        self::assertNull($view->getAttribute('option5'));
    }
}
