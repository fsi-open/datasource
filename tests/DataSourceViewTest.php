<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceView;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Exception\DataSourceViewException;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DataSourceView.
 */
class DataSourceViewTest extends TestCase
{
    /**
     * Checks creation of view.
     */
    public function testViewCreate()
    {
        $datasource = $this->createDatasourceMock();
        $datasource
            ->expects($this->once())
            ->method('getName')
            ->willReturn('ds')
        ;

        $view = new DataSourceView($datasource);
        $this->assertEquals($view->getName(), 'ds');
    }

    /**
     * Checks if view properly proxy some requests to datasource.
     */
    public function testGetParameters()
    {
        $datasource = $this->createDatasourceMock();

        $datasource
            ->expects($this->once())
            ->method('getParameters')
            ->willReturn(['datasource' => []])
        ;

        $datasource
            ->expects($this->once())
            ->method('getOtherParameters')
            ->willReturn(['other_datasource' => []])
        ;

        $view = new DataSourceView($datasource);
        $view->getParameters();
        $view->getOtherParameters();
        $allParameters = $view->getAllParameters();

        $this->assertEquals(
            $allParameters,
            ['datasource' => [], 'other_datasource' => []]
        );
    }

    /**
     * Checks if view properly proxy some requests to datasource.
     */
    public function testGetResults()
    {
        $datasource = $this->createDatasourceMock();

        $datasource
            ->expects($this->once())
            ->method('getResult')
            ->willReturn(new ArrayCollection());

        $view = new DataSourceView($datasource);
        $view->getParameters();

        $this->assertInstanceOf(ArrayCollection::class, $view->getResult());
    }

    /**
     * Checks the correctness of options related methods.
     */
    public function testOptionsManipulation()
    {
        $view = new DataSourceView($this->createDatasourceMock());

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

        $this->assertEquals($view->getAttributes(), ['option2' => null, 'option3' => 'value3', 'option4' => 'value4']);

        $this->assertEquals(null, $view->getAttribute('option2'));
    }

    /**
     * Checks the correctness of field related methods.
     */
    public function testFieldsManipulation()
    {
        $fieldView1 = $this->createMock(FieldViewInterface::class);
        $fieldView2 = $this->createMock(FieldViewInterface::class);
        $view = new DataSourceView($this->createDatasourceMock());

        $fieldView1
            ->expects($this->any())
            ->method('getName')
            ->willReturn('name1')
        ;

        $fieldView2
            ->expects($this->any())
            ->method('getName')
            ->willReturn('name2')
        ;

        $view->addField($fieldView1);
        $view->addField($fieldView2);

        $this->assertCount(2, $view->getFields());
        $this->assertTrue($view->hasField('name1'));
        $this->assertTrue($view->hasField('name2'));
        $this->assertFalse($view->hasField('wrong'));

        //Should be no exception throwed.
        $view->getField('name1');
        $view->getField('name2');

        $this->expectException(DataSourceViewException::class);
        $view->addField($fieldView1);
    }

    /**
     * Checks exception throwed when adding another field with the same name.
     */
    public function testAddFieldException2()
    {
        $fieldView = $this->createMock(FieldViewInterface::class);
        $view = new DataSourceView($this->createDatasourceMock());

        $fieldView
            ->expects($this->any())
            ->method('getName')
            ->willReturn('name')
        ;

        $view->addField($fieldView);

        $view->getField('name');
        $this->expectException(DataSourceViewException::class);
        $view->getField('wrong');
    }

    /**
     * Checks implementation of \Countable, \SeekableIterator and \ArrayAccess interface.
     */
    public function testInterfaces()
    {
        for ($x = 0; $x < 5; $x++) {
            $fieldView = $this->createMock(FieldViewInterface::class);

            $fieldView
                ->expects($this->any())
                ->method('getName')
                ->willReturn("name$x")
            ;

            $fieldsViews[] = $fieldView;
        }

        $view = new DataSourceView($this->createDatasourceMock());
        $view->addField($fieldsViews[0]);

        $this->assertCount(1, $view);
        $this->assertTrue(isset($view['name0']));
        $this->assertFalse(isset($view['name1']));

        foreach ($view as $key => $value) {
            $this->assertEquals('name0', $key);
        }

        $view->addField($fieldsViews[1]);
        $view->addField($fieldsViews[2]);

        $this->assertEquals('name0', $view->key());
        $view->next();
        $this->assertEquals('name1', $view->key());

        $view->addField($fieldsViews[3]);
        $view->addField($fieldsViews[4]);

        //After adding fields iterator resets on its own.
        $this->assertEquals('name0', $view->key());

        $this->assertCount(5, $view);
        $this->assertTrue(isset($view['name1']));

        $view->seek(1);
        $this->assertEquals('name1', $view->current()->getName());
        $this->assertEquals('name1', $view->key());

        $fields = [];
        for ($view->rewind(); $view->valid(); $view->next()) {
            $fields[] = $view->current()->getName();
        }

        $expected = ['name0', 'name1', 'name2', 'name3', 'name4'];
        $this->assertEquals($expected, $fields);

        $this->assertEquals('name3', $view['name3']->getName());

        //Checking fake methods.
        $view['name0'] = 'trash';
        $this->assertNotEquals('trash', $view['name0']);
        unset($view['name0']);
        $this->assertTrue(isset($view['name0']));
    }

    /**
     * @return MockObject|DataSourceInterface
     */
    private function createDatasourceMock()
    {
        return $this->getMockBuilder(DataSourceInterface::class)
            ->setConstructorArgs([$this->createMock(DriverInterface::class)])
            ->getMock();
    }
}
