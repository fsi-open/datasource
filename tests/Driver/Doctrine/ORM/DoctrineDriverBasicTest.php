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
    /**
     * @return array<array<string>>
     */
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
     * @return MockObject&EntityManagerInterface
     */
    private function getEntityManagerMock(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @param MockObject&EntityManagerInterface $em
     * @return MockObject&QueryBuilder
     */
    private function getQueryBuilderMock(EntityManagerInterface $em): QueryBuilder
    {
        $qb = $this->getMockBuilder(QueryBuilder::class)->setConstructorArgs([$em])->getMock();
        $qb->method('select')->willReturn($qb);
        $qb->method('from')->willReturn($qb);

        $em->method('createQueryBuilder')->willReturn($qb);

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

        $qb->method('getRootEntities')->willReturn(['entity']);
        $qb->method('getEntityManager')->willReturn($em);

        $em->method('getClassMetadata')->willReturnMap($returnMap);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCreation(): void
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getQueryBuilderMock($em);
        new DoctrineDriver([], $em, 'entity');
        new DoctrineDriver([], $em, $qb);
    }

    /**
     * Checks creation exception.
     */
    public function testCreationException3(): void
    {
        $this->expectException(DataSourceException::class);
        $em = $this->getEntityManagerMock();
        $this->getQueryBuilderMock($em);
        new DoctrineDriver([new stdClass()], $em, 'entity');
    }

    /**
     * Checks creation exception.
     */
    public function testCreationException4(): void
    {
        $this->expectException(DoctrineDriverException::class);

        $em = $this->getEntityManagerMock();
        $this->getQueryBuilderMock($em);

        new DoctrineDriver([], $em, null);
    }

    /**
     * Checks basic getResult call.
     */
    public function testGetResult(): void
    {
        $fields = [];

        for ($x = 0; $x < 6; $x++) {
            $field = $this->createMock(DoctrineAbstractField::class);
            $field->expects(self::once())->method('buildQuery');

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
    public function testGetResultException1(): void
    {
        $fields = [$this->createMock(FieldTypeInterface::class)];

        $em = $this->getEntityManagerMock();
        $qb = $this->getMockBuilder(QueryBuilder::class)->setConstructorArgs([$em])->getMock();

        $em->method('createQueryBuilder')->willReturn($qb);
        $qb->method('select')->willReturn($qb);

        $driver = new DoctrineDriver([], $em, 'entity');
        $this->expectException(DoctrineDriverException::class);
        $driver->getResult($fields, 0, 20);
    }

    /**
     * Checks exception when trying to access the query builder not during getResult method.
     */
    public function testGetQueryException(): void
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
    public function testCoreExtension(): void
    {
        $em = $this->getEntityManagerMock();
        $this->getQueryBuilderMock($em);
        $driver = new DoctrineDriver([new CoreExtension()], $em, 'entity');

        self::assertTrue($driver->hasFieldType('text'));
        self::assertTrue($driver->hasFieldType('number'));
        self::assertTrue($driver->hasFieldType('entity'));
        self::assertTrue($driver->hasFieldType('date'));
        self::assertTrue($driver->hasFieldType('time'));
        self::assertTrue($driver->hasFieldType('datetime'));
        self::assertTrue($driver->hasFieldType('boolean'));
        self::assertFalse($driver->hasFieldType('wrong'));
        self::assertFalse($driver->hasFieldType(null));

        $driver->getFieldType('text');
        $this->expectException(DataSourceException::class);
        $driver->getFieldType('wrong');
    }

    /**
     * Checks all fields of CoreExtension.
     *
     * @dataProvider fieldNameProvider
     */
    public function testCoreFields($type): void
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getQueryBuilderMock($em);
        $this->extendWithRootEntities($em, $qb);

        $driver = new DoctrineDriver([new CoreExtension()], $em, 'entity');
        self::assertTrue($driver->hasFieldType($type));
        $field = $driver->getFieldType($type);
        self::assertInstanceOf(FieldTypeInterface::class, $field);
        self::assertInstanceOf(DoctrineFieldInterface::class, $field);

        self::assertTrue($field->getOptionsResolver()->isDefined('field'));

        $comparisons = $field->getAvailableComparisons();
        self::assertGreaterThan(0, count($comparisons));

        foreach ($comparisons as $cmp) {
            $field = $driver->getFieldType($type);
            $field->setName('name');
            $field->setComparison($cmp);
            $field->setOptions([]);
        }

        self::assertEquals($field->getOption('field'), $field->getName());

        $this->expectException(FieldException::class);
        $field = $driver->getFieldType($type);
        $field->setComparison('wrong');
    }

    /**
     * Checks extensions calls.
     */
    public function testExtensionsCalls(): void
    {
        $em = $this->getEntityManagerMock();
        $qb = $this->getQueryBuilderMock($em);
        $this->extendWithRootEntities($em, $qb);

        $extension = new DoctrineDriverExtension();
        $driver = new DoctrineDriver([], $em, 'entity');
        $driver->addExtension($extension);

        $driver->getResult([], 0, 20);
        self::assertEquals(['preGetResult', 'postGetResult'], $extension->getCalls());

        $this->expectException(DoctrineDriverException::class);
        $driver->getQueryBuilder();
    }

    /**
     * Checks fields extensions calls.
     */
    public function testFieldsExtensionsCalls(): void
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
            self::assertEquals(['preBindParameter', 'postBindParameter'], $extension->getCalls());
            $extension->resetCalls();

            $field->getParameter($parameter);
            self::assertEquals(['postGetParameter'], $extension->getCalls());
            $extension->resetCalls();

            $field->createView();
            self::assertEquals(['postBuildView'], $extension->getCalls());
            $extension->resetCalls();
        }
    }
}
