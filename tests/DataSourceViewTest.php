<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Component\DataSource\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use FSi\Component\DataSource\DataSource;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceView;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Exception\DataSourceViewException;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DataSourceViewTest extends TestCase
{
    /**
     * Checks creation of view.
     */
    public function testViewCreate(): void
    {
        $datasource = $this->createDatasourceMock();
        $datasource->expects(self::once())->method('getName')->willReturn('ds');

        $view = new DataSourceView($datasource);
        self::assertEquals($view->getName(), 'ds');
    }

    /**
     * Checks if view properly proxy some requests to datasource.
     */
    public function testGetParameters(): void
    {
        $datasource = $this->createDatasourceMock();
        $datasource->expects(self::once())->method('getParameters')->willReturn(['datasource' => []]);
        $datasource->expects(self::once())->method('getOtherParameters')->willReturn(['other_datasource' => []]);

        $view = new DataSourceView($datasource);
        $view->getParameters();
        $view->getOtherParameters();
        $allParameters = $view->getAllParameters();

        self::assertEquals(
            ['datasource' => [], 'other_datasource' => []],
            $allParameters
        );
    }

    /**
     * Checks if view properly proxy some requests to datasource.
     */
    public function testGetResults(): void
    {
        $datasource = $this->createDatasourceMock();
        $datasource->expects(self::once())->method('getResult')->willReturn(new ArrayCollection());

        $view = new DataSourceView($datasource);
        $view->getParameters();

        self::assertInstanceOf(ArrayCollection::class, $view->getResult());
    }

    /**
     * Checks the correctness of options related methods.
     */
    public function testOptionsManipulation(): void
    {
        $view = new DataSourceView($this->createDatasourceMock());

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

        self::assertEquals(null, $view->getAttribute('option2'));
    }

    /**
     * Checks the correctness of field related methods.
     */
    public function testFieldsManipulation(): void
    {
        $view = new DataSourceView($this->createDatasourceMock());

        $fieldView1 = $this->createMock(FieldViewInterface::class);
        $fieldView1->method('getName')->willReturn('name1');

        $fieldView2 = $this->createMock(FieldViewInterface::class);
        $fieldView2->method('getName')->willReturn('name2');

        $view->addField($fieldView1);
        $view->addField($fieldView2);

        self::assertCount(2, $view->getFields());
        self::assertTrue($view->hasField('name1'));
        self::assertTrue($view->hasField('name2'));
        self::assertFalse($view->hasField('wrong'));

        // Should be no exception thrown.
        $view->getField('name1');
        $view->getField('name2');

        $this->expectException(DataSourceViewException::class);
        $view->addField($fieldView1);
    }

    /**
     * Checks exception thrown when adding another field with the same name.
     */
    public function testAddFieldException2(): void
    {
        $view = new DataSourceView($this->createDatasourceMock());

        $fieldView = $this->createMock(FieldViewInterface::class);
        $fieldView->method('getName')->willReturn('name');

        $view->addField($fieldView);

        $view->getField('name');
        $this->expectException(DataSourceViewException::class);
        $view->getField('wrong');
    }

    /**
     * Checks implementation of \Countable, \SeekableIterator and \ArrayAccess interface.
     */
    public function testInterfaces(): void
    {
        $fieldsViews = [];
        for ($x = 0; $x < 5; $x++) {
            $fieldView = $this->createMock(FieldViewInterface::class);

            $fieldView
                ->method('getName')
                ->willReturn("name$x")
            ;

            $fieldsViews[] = $fieldView;
        }

        $view = new DataSourceView($this->createDatasourceMock());
        $view->addField($fieldsViews[0]);

        self::assertCount(1, $view);
        self::assertTrue(isset($view['name0']));
        self::assertFalse(isset($view['name1']));

        foreach ($view as $key => $value) {
            self::assertEquals('name0', $key);
        }

        $view->addField($fieldsViews[1]);
        $view->addField($fieldsViews[2]);

        self::assertEquals('name0', $view->key());
        $view->next();
        self::assertEquals('name1', $view->key());

        $view->addField($fieldsViews[3]);
        $view->addField($fieldsViews[4]);

        // After adding fields iterator resets on its own.
        self::assertEquals('name0', $view->key());

        self::assertCount(5, $view);
        self::assertTrue(isset($view['name1']));

        $view->seek(1);
        self::assertEquals('name1', $view->current()->getName());
        self::assertEquals('name1', $view->key());

        $fields = [];
        for ($view->rewind(); $view->valid(); $view->next()) {
            $fields[] = $view->current()->getName();
        }

        $expected = ['name0', 'name1', 'name2', 'name3', 'name4'];
        self::assertEquals($expected, $fields);

        self::assertEquals('name3', $view['name3']->getName());

        // Checking fake methods.
        $view['name0'] = 'trash';
        self::assertNotEquals('trash', $view['name0']);
        unset($view['name0']);
        self::assertTrue(isset($view['name0']));
    }

    /**
     * @return MockObject&DataSourceInterface
     */
    private function createDatasourceMock(): DataSourceInterface
    {
        return $this->getMockBuilder(DataSourceInterface::class)->getMock();
    }
}
