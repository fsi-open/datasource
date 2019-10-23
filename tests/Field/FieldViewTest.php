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
    public function testCreate()
    {
        /** @var FieldTypeInterface|MockObject $field */
        $field = $this->createMock(FieldTypeInterface::class);

        $field
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('somename')
        ;

        $field
            ->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn('sometype')
        ;

        $field
            ->expects($this->atLeastOnce())
            ->method('getComparison')
            ->willReturn('somecomp')
        ;

        $field
            ->expects($this->atLeastOnce())
            ->method('getCleanParameter')
            ->willReturn('someparam')
        ;

        $fieldView = new FieldView($field);

        $this->assertEquals($field->getName(), $fieldView->getName());
        $this->assertEquals($field->getType(), $fieldView->getType());
        $this->assertEquals($field->getComparison(), $fieldView->getComparison());
        $this->assertEquals($field->getCleanParameter(), $fieldView->getParameter());
    }

    /**
     * Checks correctness of reference to DataSourceView.
     */
    public function testSetDataSourceView()
    {
        $driver = $this->createMock(DriverInterface::class);
        $datasource = $this->getMockBuilder(DataSource::class)->setConstructorArgs([$driver])->getMock();
        $view = $this->getMockBuilder(DataSourceView::class)->setConstructorArgs([$datasource])->getMock();
        $field = $this->createMock(FieldTypeInterface::class);
        $fieldView = new FieldView($field);

        $fieldView->setDataSourceView($view);
        $this->assertEquals($fieldView->getDataSourceView(), $view);
    }

    /**
     * Checks the correctness of options related methods.
     */
    public function testOptionsManipulation()
    {
        $field = $this->createMock(FieldTypeInterface::class);
        $view = new FieldView($field);

        $this->assertFalse($view->hasAttribute('option1'));
        $view->setAttribute('option1', 'value1');
        $this->assertTrue($view->hasAttribute('option1'));
        $this->assertEquals($view->getAttribute('option1'), 'value1');
        $view->removeAttribute('option1');
        $this->assertFalse($view->hasAttribute('option1'));

        $view->setAttribute('option2', '');
        $this->assertTrue($view->hasAttribute('option2'));

        $view->setAttribute('option2', null);
        $this->assertTrue($view->hasAttribute('option2'));

        $view->setAttribute('option3', 'value3');
        $view->setAttribute('option4', 'value4');

        $this->assertEquals(['option2' => null, 'option3' => 'value3', 'option4' => 'value4'], $view->getAttributes());

        $this->assertNull($view->getAttribute('option5'));
    }
}
