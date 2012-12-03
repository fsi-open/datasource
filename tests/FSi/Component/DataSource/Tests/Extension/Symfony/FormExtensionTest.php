<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieślik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Extension\Symfony;

use FSi\Component\DataSource\Extension\Symfony\Form\FormExtension;
use FSi\Component\DataSource\Extension\Symfony\Form\Driver\DriverExtension;
use FSi\Component\DataSource\Field\FieldAbstractExtension;
use Symfony\Component\Form;
use FSi\Component\DataSource\DataSourceInterface;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use FSi\Component\DataSource\Tests\Fixtures\TestManagerRegistry;

/**
 * Tests for Symfony Form Extension.
 */
class FormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        if (!class_exists('Symfony\Component\Form\Form')) {
            $this->markTestSkipped('Symfony Form needed!');
        }
    }

    /**
     * Returns mock of FormFactory.
     *
     * @return object
     */
    private function getFormFactory()
    {
        $typeFactory = new Form\ResolvedFormTypeFactory();
        $registry = new Form\FormRegistry(
            array(
                new Form\Extension\Core\CoreExtension(),
                new Form\Extension\Csrf\CsrfExtension(
                    new Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider('secret')
                ),
            ),
            $typeFactory
        );
        return new Form\FormFactory($registry, $typeFactory);
    }

    /**
     * Checks creation of FormExtension.
     */
    public function testFormExtension()
    {
        $formFactory = $this->getFormFactory();
        $extension = new FormExtension($formFactory);

        $this->setExpectedException('Exception');
        $extension = new FormExtension(new \stdClass());
    }

    /**
     * Checks creation of DriverExtension.
     */
    public function testCreateDriverExtension()
    {
        $formFactory = $this->getFormFactory();
        $extension = new DriverExtension($formFactory);

        $this->setExpectedException('Exception');
        $extension = new DriverExtension(new \stdClass());
    }

    /**
     * Tests if driver extension has all needed fields.
     */
    public function testDriverExtension()
    {
        $formFactory = $this->getFormFactory();
        $extension = new DriverExtension($formFactory);

        $this->assertTrue($extension->hasFieldTypeExtensions('text'));
        $this->assertTrue($extension->hasFieldTypeExtensions('number'));
        $this->assertTrue($extension->hasFieldTypeExtensions('entity'));
        $this->assertTrue($extension->hasFieldTypeExtensions('date'));
        $this->assertTrue($extension->hasFieldTypeExtensions('time'));
        $this->assertTrue($extension->hasFieldTypeExtensions('datetime'));
        $this->assertFalse($extension->hasFieldTypeExtensions('wrong'));

        $extension->getFieldTypeExtensions('text');
        $extension->getFieldTypeExtensions('number');
        $extension->getFieldTypeExtensions('entity');
        $extension->getFieldTypeExtensions('date');
        $extension->getFieldTypeExtensions('time');
        $extension->getFieldTypeExtensions('datetime');
        $this->setExpectedException('FSi\Component\DataSource\Exception\DataSourceException');
        $extension->getFieldTypeExtensions('wrong');
    }

    /**
     * Checks fields behaviour.
     *
     * @TODO: Checking entity field.
     */
    public function testFields()
    {
        foreach (array('text', 'number', 'date', 'time', 'datetime') as $type) {
            $formFactory = $this->getFormFactory();
            $extension = new DriverExtension($formFactory);
            $text = $extension->getFieldTypeExtensions('text');
            $field = $this->getMock('FSi\Component\DataSource\Field\FieldTypeInterface');
            $driver = $this->getMock('FSi\Component\DataSource\Driver\DriverInterface');
            $datasource = $this->getMock('FSi\Component\DataSource\DataSource', array(), array($driver));

            $datasource
                ->expects($this->any())
                ->method('getName')
                ->will($this->returnValue('datasource'))
            ;

            $field
                ->expects($this->atLeastOnce())
                ->method('getName')
                ->will($this->returnValue('name'))
            ;

            $field
                ->expects($this->any())
                ->method('getDataSource')
                ->will($this->returnValue($datasource))
            ;

            $field
                ->expects($this->any())
                ->method('getName')
                ->will($this->returnValue('name'))
            ;

            $extensions = $extension->getFieldTypeExtensions($type);

            $parameters = array('datasource' => array(DataSourceInterface::FIELDS => array('name' => 'value')));
            $parameters2 = $parameters;
            foreach ($extensions as $ext) {
                $this->assertTrue($ext instanceof FieldAbstractExtension);
                $ext->preBindParameter($field, $parameters);
            }
            $this->assertEquals($parameters2, $parameters);
            $fieldView = $this->getMock('FSi\Component\DataSource\Field\FieldViewInterface', array(), array($field));

            $fieldView
                ->expects($this->atLeastOnce())
                ->method('setOption')
            ;

            foreach ($extensions as $ext) {
                $ext->postBuildView($field, $fieldView);
            }
        }
    }


}