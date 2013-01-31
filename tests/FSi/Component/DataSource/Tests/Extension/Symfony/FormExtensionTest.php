<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\Event\FieldEvent;

/**
 * Tests for Symfony Form Extension.
 */
class FormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Provides types.
     *
     * @return array
     */
    public static function typesProvider()
    {
        return array(
            array('text'),
            array('number'),
            array('date'),
            array('time'),
            array('datetime'),
        );
    }

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
     * @dataProvider typesProvider
     */
    public function testFields($type)
    {
        $formFactory = $this->getFormFactory();
        $extension = new DriverExtension($formFactory);
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
            ->method('getType')
            ->will($this->returnValue($type))
        ;

        $field
            ->expects($this->any())
            ->method('hasOption')
            ->will($this->returnCallback(function($option) use ($type) {
                return (($type == 'number') && ($option =='form_type'));
            }))
        ;

        $field
            ->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function($option) use ($type) {
                switch ($option) {
                    case 'form_filter':
                        return true;
                    case 'form_type':
                        if ($type == 'number') {
                            return 'text';
                        } else {
                            return null;
                        }
                }
            }))
        ;

        $extensions = $extension->getFieldTypeExtensions($type);

        if ($type == 'datetime') {
            $parameters = array('datasource' => array(DataSourceInterface::PARAMETER_FIELDS => array('name' =>
                array(
                    'date' => array('year' => 2012, 'month' => 12, 'day' => 12),
                    'time' => array('hour' => 12, 'minute' => 12),
                ),
            )));
            $parameters2 = array('datasource' => array(DataSourceInterface::PARAMETER_FIELDS => array('name' => new \DateTime('2012-12-12 12:12:00'))));
        } elseif ($type == 'time') {
            $parameters = array('datasource' => array(DataSourceInterface::PARAMETER_FIELDS => array('name' =>
                array(
                    'hour' => 12,
                    'minute' => 12,
                ),
            )));
            $parameters2 = array('datasource' => array(DataSourceInterface::PARAMETER_FIELDS => array('name' => new \DateTime(date('Y-m-d', 0).' 12:12:00'))));
        } elseif ($type == 'date') {
            $parameters = array('datasource' => array(DataSourceInterface::PARAMETER_FIELDS => array('name' =>
                array(
                    'year' => 2012,
                    'month' => 12,
                    'day' => 12,
                ),
            )));
            $parameters2 = array('datasource' => array(DataSourceInterface::PARAMETER_FIELDS => array('name' => new \DateTime('2012-12-12'))));
        } elseif ($type == 'number') {
            $parameters = array('datasource' => array(DataSourceInterface::PARAMETER_FIELDS => array('name' => 123)));
            $parameters2 = $parameters;
        } else {
            $parameters = array('datasource' => array(DataSourceInterface::PARAMETER_FIELDS => array('name' => 'value')));
            $parameters2 = $parameters;
        }

        $args = new FieldEvent\ParameterEventArgs($field, $parameters);
        foreach ($extensions as $ext) {
            $this->assertTrue($ext instanceof FieldAbstractExtension);
            $ext->preBindParameter($args);
        }
        $parameters = $args->getParameter();
        $this->assertEquals($parameters2, $parameters);
        $fieldView = $this->getMock('FSi\Component\DataSource\Field\FieldViewInterface', array(), array($field));

        $fieldView
            ->expects($this->atLeastOnce())
            ->method('setAttribute')
            ->will($this->returnCallback(function ($attribute, $value) use ($type) {
                if ($attribute == 'form') {
                    $this->assertInstanceOf('\Symfony\Component\Form\FormView', $value);
                }
            }))
        ;

        $args = new FieldEvent\ViewEventArgs($field, $fieldView);
        foreach ($extensions as $ext) {
            $ext->postBuildView($args);
        }
    }
}
