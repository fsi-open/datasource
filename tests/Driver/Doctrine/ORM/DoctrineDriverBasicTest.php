<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Tests\Driver\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineAbstractField;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineDriver;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineFieldInterface;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Exception\DoctrineDriverException;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\CoreExtension;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Exception\FieldException;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Tests\Fixtures\DoctrineDriverExtension;
use FSi\Component\DataSource\Tests\Fixtures\FieldExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class DoctrineDriverBasicTest extends TestCase
{
    public static function fieldNameProvider(): array
    {
        return [
            ['text'],
            ['number'],
            ['entity'],
            ['date'],
            ['time'],
            ['datetime'],
            ['boolean'],
        ];
    }

    /**
     * Returns mock of EntityManager.
     *
     * @return MockObject|EntityManagerInterface
     */
    private function getEntityManagerMock()
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @param MockObject|EntityManagerInterface $em
     * @return MockObject|QueryBuilder
     */
    private function getQueryBuilderMock(EntityManagerInterface $em)
    {
        $qb = $this->getMockBuilder(QueryBuilder::class)->setConstructorArgs([$em])->getMock();

        $em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $qb
            ->expects($this->any())
            ->method('select')
            ->willReturn($qb)
        ;

        $qb
            ->expects($this->any())
            ->method('from')
            ->willReturn($qb)
        ;

        return $qb;
    }

    /**
     * @param MockObject|EntityManagerInterface $em
     * @param MockObject|QueryBuilder $qb
     * @param array $map
     */
    private function extendWithRootEntities(EntityManagerInterface $em, $qb, array $map = [['entity', true]]): void
    {
        $returnMap = [];
        foreach ($map as $info) {
            /** @var MockObject|ClassMetadata $metadata */
            $metadata = $this->createMock(ClassMetadata::class);
            $metadata->isIdentifierComposite = $info[1];

            $returnMap[] = [$info[0], $metadata];
        }

        $qb
            ->expects($this->any())
            ->method('getRootEntities')
            ->willReturn(['entity'])
        ;
        $qb->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em)
        ;

        $em
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValueMap($returnMap))
        ;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCreation()
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getQueryBuilderMock($em);
        new DoctrineDriver([], $em, 'entity');
        new DoctrineDriver([], $em, $qb);
    }

    /**
     * Checks creation exception.
     */
    public function testCreationException3()
    {
        $this->expectException(DataSourceException::class);
        $em = $this->getEntityManagerMock();
        $this->getQueryBuilderMock($em);
        new DoctrineDriver([new stdClass()], $em, 'entity');
    }

    /**
     * Checks creation exception.
     */
    public function testCreationException4()
    {
        $this->expectException(DoctrineDriverException::class);

        $em = $this->getEntityManagerMock();
        $this->getQueryBuilderMock($em);

        new DoctrineDriver([], $em, null);
    }

    /**
     * Checks basic getResult call.
     */
    public function testGetResult()
    {
        $fields = [];

        for ($x = 0; $x < 6; $x++) {
            $field = $this->createMock(DoctrineAbstractField::class);

            $field
                ->expects($this->once())
                ->method('buildQuery')
            ;

            $fields[] = $field;
        }

        $em = $this->getEntityManagerMock();
        $qb = $this->getQueryBuilderMock($em);
        $this->extendWithRootEntities($em, $qb);

        $driver = new DoctrineDriver([], $em, 'entity');
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks exception when fields aren't proper instances.
     */
    public function testGetResultException1()
    {
        $fields = [$this->createMock(FieldTypeInterface::class)];

        $em = $this->getEntityManagerMock();
        $qb = $this->getMockBuilder(QueryBuilder::class)->setConstructorArgs([$em])->getMock();

        $em
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $qb
            ->expects($this->any())
            ->method('select')
            ->willReturn($qb)
        ;

        $driver = new DoctrineDriver([], $em, 'entity');
        $this->expectException(DoctrineDriverException::class);
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks exception when trying to access the query builder not during getResult method.
     */
    public function testGetQueryException()
    {
        $em = $this->getEntityManagerMock();
        $this->getQueryBuilderMock($em);

        $driver = new DoctrineDriver([], $em, 'entity');
        $this->expectException(DoctrineDriverException::class);
        $driver->getQueryBuilder();
    }

    /**
     * Checks CoreExtension.
     */
    public function testCoreExtension()
    {
        $em = $this->getEntityManagerMock();
        $this->getQueryBuilderMock($em);
        $driver = new DoctrineDriver([new CoreExtension()], $em, 'entity');

        $this->assertTrue($driver->hasFieldType('text'));
        $this->assertTrue($driver->hasFieldType('number'));
        $this->assertTrue($driver->hasFieldType('entity'));
        $this->assertTrue($driver->hasFieldType('date'));
        $this->assertTrue($driver->hasFieldType('time'));
        $this->assertTrue($driver->hasFieldType('datetime'));
        $this->assertTrue($driver->hasFieldType('boolean'));
        $this->assertFalse($driver->hasFieldType('wrong'));
        $this->assertFalse($driver->hasFieldType(null));

        $driver->getFieldType('text');
        $this->expectException(DataSourceException::class);
        $driver->getFieldType('wrong');
    }

    /**
     * Checks all fields of CoreExtension.
     *
     * @dataProvider fieldNameProvider
     */
    public function testCoreFields($type)
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getQueryBuilderMock($em);
        $this->extendWithRootEntities($em, $qb);

        $driver = new DoctrineDriver([new CoreExtension()], $em, 'entity');
        $this->assertTrue($driver->hasFieldType($type));
        $field = $driver->getFieldType($type);
        $this->assertInstanceOf(FieldTypeInterface::class, $field);
        $this->assertInstanceOf(DoctrineFieldInterface::class, $field);

        $this->assertTrue($field->getOptionsResolver()->isDefined('field'));

        $comparisons = $field->getAvailableComparisons();
        $this->assertTrue(count($comparisons) > 0);

        foreach ($comparisons as $cmp) {
            $field = $driver->getFieldType($type);
            $field->setName('name');
            $field->setComparison($cmp);
            $field->setOptions([]);
        }

        $this->assertEquals($field->getOption('field'), $field->getName());

        $this->expectException(FieldException::class);
        $field = $driver->getFieldType($type);
        $field->setComparison('wrong');
    }

    /**
     * Checks extensions calls.
     */
    public function testExtensionsCalls()
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getQueryBuilderMock($em);
        $this->extendWithRootEntities($em, $qb);

        $extension = new DoctrineDriverExtension();
        $driver = new DoctrineDriver([], $em, 'entity');
        $driver->addExtension($extension);

        $driver->getResult([], 0, 20);
        $this->assertEquals(['preGetResult', 'postGetResult'], $extension->getCalls());

        $this->expectException(DoctrineDriverException::class);
        $driver->getQueryBuilder();
    }

    /**
     * Checks fields extensions calls.
     */
    public function testFieldsExtensionsCalls()
    {
        $extension = new FieldExtension();
        $parameter = [];
        $fields = [
            new Field\Text(), new Field\Number(), new Field\Date(), new Field\Time(),
            new Field\DateTime(), new Field\Entity()
        ];

        foreach ($fields as $field) {
            $field->addExtension($extension);

            $field->bindParameter([]);
            $this->assertEquals(['preBindParameter', 'postBindParameter'], $extension->getCalls());
            $extension->resetCalls();

            $field->getParameter($parameter);
            $this->assertEquals(['postGetParameter'], $extension->getCalls());
            $extension->resetCalls();

            $field->createView([]);
            $this->assertEquals(['postBuildView'], $extension->getCalls());
            $extension->resetCalls();
        }
    }
}
