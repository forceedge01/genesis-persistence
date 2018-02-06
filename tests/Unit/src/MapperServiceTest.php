<?php

namespace Genesis\Services\Test\Persistence;

use Genesis\Services\Persistence\MapperService;
use Genesis\Services\Persistence\DatabaseService;
use Genesis\Services\Persistence\Model\BaseModel;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

class MapperModelTester extends BaseModel
{
    protected $userId = 'int not null';

    protected $name = 'text not null';

    protected function getRequiredFields()
    {
        return ['userId'];
    }
}

class MapperModelTester2
{
    protected $userId = 'int not null';

    protected $name = 'text not null';

    protected function getRequiredFields()
    {
        return ['userId'];
    }
}

class User extends BaseModel
{
    protected function getRequiredFields()
    {
        return [];
    }
}

class MapperServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var object  The object to be tested.
     */
    private $testObject;

    /**
     * @var ReflectionClass The reflection class.
     */
    private $reflection;

    /**
     * @var array  The test object dependencies.
     */
    private $dependencies = [];

    /**
     * Set up the testing object.
     */
    public function setUp()
    {
        $this->dependencies = [
            'databaseService' => $databaserServiceMock = $this->getMockBuilder(DatabaseService::class)
                ->disableOriginalConstructor()
                ->getMock()
        ];

        $this->reflection = new ReflectionClass(MapperService::class);
        $this->testObject = $this->reflection->newInstanceArgs($this->dependencies);
    }

    /**
     * testCreateTable Test that createTable executes as expected.
     */
    public function testCreateTableWithClassName()
    {
        // Mock
        $this->dependencies['databaseService']->expects($this->once())
            ->method('execute')
            ->with('CREATE TABLE IF NOT EXISTS `MapperModelTester` (`userId` int not null, `name` text not null, `id` integer PRIMARY KEY)')
            ->willReturn(true);

        // Execute
        $result = $this->testObject->createTable(MapperModelTester::class);

        // Assert Result
        $this->assertTrue($result);
    }

    /**
     * testCreateTable Test that createTable executes as expected.
     */
    public function testCreateTableWithClassObject()
    {
        // Prepare / Mock
        $obj = MapperModelTester::getNew([
            'userId' => null
        ]);

        $this->dependencies['databaseService']->expects($this->once())
            ->method('execute')
            ->with('CREATE TABLE IF NOT EXISTS `MapperModelTester` (`userId` int not null, `name` text not null, `id` integer PRIMARY KEY)')
            ->willReturn(true);

        // Execute
        $result = $this->testObject->createTable(MapperModelTester::class);

        // Assert Result
        $this->assertTrue($result);
    }

    /**
     * testPersist Test that persist executes as expected.
     */
    public function testPersistCreatesNew()
    {
        // Prepare / Mock
        $obj = MapperModelTester::getNew(['userId' => 15]);
        $obj->setName('Abdul');
        $obj->setUserId(1555);

        $this->dependencies['databaseService']->expects($this->once())
            ->method('save')
            ->with('MapperModelTester', ['name' => 'Abdul', 'userId' => 1555])
            ->willReturn(15);
        $this->dependencies['databaseService']->expects($this->never())
            ->method('update');

        // Execute
        $result = $this->testObject->persist($obj);

        // Assert Result
        $this->assertEquals($result, $obj);
        $this->assertEquals(15, $obj->getId());
    }

    /**
     * testPersist Test that persist executes as expected.
     */
    public function testPersistUpdatesExisting()
    {
        // Prepare / Mock
        $obj = MapperModelTester::getNew(['userId' => 15]);
        $obj->setName('Abdul');
        $obj->setUserId(1555);
        $obj->setId(23);

        $this->dependencies['databaseService']->expects($this->once())
            ->method('update')
            ->with('MapperModelTester', ['name' => 'Abdul', 'userId' => 1555, 'id' => 23], ['id' => 23])
            ->willReturn($obj);
        $this->dependencies['databaseService']->expects($this->never())
            ->method('save');

        // Execute
        $result = $this->testObject->persist($obj);

        // Assert Result
        $this->assertEquals($result, $obj);
        $this->assertEquals(23, $obj->getId());
    }

    /**
     * testGet Test that get executes as expected.
     *
     * @expectedException Exception
     */
    public function testGetThrowException()
    {
        // Prepare / Mock
        $invalidModel = MapperModelTester2::class;

        // Execute
        $this->testObject->get($invalidModel);
    }

    /**
     * testGet Test that get executes as expected.
     */
    public function testGetWithArgs()
    {
        // Prepare / Mock
        $validModel = MapperModelTester::class;
        $args = ['name' => 'abdul'];
        $order = ['id' => 'desc'];

        $this->dependencies['databaseService']->expects($this->once())
            ->method('get')
            ->with('MapperModelTester', $args, $order)
            ->willReturn([]);
        $this->dependencies['databaseService']->expects($this->never())
            ->method('getAll');

        // Execute
        $result = $this->testObject->get($validModel, $args, $order);

        // Assert Result
        $this->assertEquals([], $result);
    }

    /**
     * testGet Test that get executes as expected.
     */
    public function testGetWithoutArgs()
    {
        // Prepare / Mock
        $validModel = MapperModelTester::class;

        $this->dependencies['databaseService']->expects($this->once())
            ->method('getAll')
            ->with('MapperModelTester')
            ->willReturn([]);
        $this->dependencies['databaseService']->expects($this->never())
            ->method('get');

        // Execute
        $result = $this->testObject->get($validModel);

        // Assert Result
        $this->assertEquals([], $result);
    }

    /**
     * testGetSingle Test that getSingle executes as expected.
     */
    public function testGetSingleReturnsNoData()
    {
        // Prepare / Mock
        $validModel = MapperModelTester::class;

        $this->dependencies['databaseService']->expects($this->once())
            ->method('getSingle')
            ->with('MapperModelTester')
            ->willReturn([]);

        // Execute
        $result = $this->testObject->getSingle($validModel);

        // Assert Result
        $this->assertFalse($result);
    }

    /**
     * testGetSingle Test that getSingle executes as expected.
     */
    public function testGetSingleReturnsData()
    {
        // Prepare / Mock
        $validModel = MapperModelTester::class;
        $expectedObject = MapperModelTester::getNew(['userId' => 15]);
        $expectedObject
            ->setId(34)
            ->setName('Abdul')
            ->setUserId(15);

        $this->dependencies['databaseService']->expects($this->once())
            ->method('getSingle')
            ->with('MapperModelTester')
            ->willReturn(['name' => 'Abdul', 'userId' => 15, 'id' => 34]);

        // Execute
        $result = $this->testObject->getSingle($validModel);

        // Assert Result
        $this->assertEquals($expectedObject, $result);
    }

    /**
     * testGetAssociated Test that getAssociated executes as expected.
     *
     * @expectedException Exception
     */
    public function testThrowExceptionIfNoAssociation()
    {
        // Prepare / Mock
        $fromObject = MapperModelTester::getNew(['userId' => 15]);

        // Execute
        $this->testObject->getAssociated(Product::class, $fromObject);
    }

    /**
     * testGetAssociated Test that getAssociated executes as expected.
     */
    public function testGetAssociated()
    {
        // Prepare / Mock
        $userId = 8827;
        $fromObject = MapperModelTester::getNew(['userId' => 15]);
        $fromObject->setUserId($userId);

        $this->dependencies['databaseService']->expects($this->once())
            ->method('getSingle')
            ->with('User', ['id' => $userId])
            ->willReturn(['id' => $userId]);

        // Execute
        $result = $this->testObject->getAssociated(User::class, $fromObject);

        $this->assertEquals($userId, $result->getId());
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * testDelete Test that delete executes as expected.
     */
    public function testDeleteWithClass()
    {
        // Prepare / Mock
        $this->dependencies['databaseService']->expects($this->once())
            ->method('delete')
            ->with('MapperModelTester', [])
            ->willReturn(true);

        // Execute
        $result = $this->testObject->delete(MapperModelTester::class);

        // Assert Result
        $this->assertTrue($result);
    }

    /**
     * testDelete Test that delete executes as expected.
     *
     * @expectedException Exception
     */
    public function testDeleteWithObjectNoId()
    {
        // Prepare / Mock
        $model = MapperModelTester::getNew(['userId' => 5]);

        $this->dependencies['databaseService']->expects($this->never())
            ->method('delete');

        // Execute
        $this->testObject->delete($model);
    }

    /**
     * testDelete Test that delete executes as expected.
     */
    public function testDeleteWithObject()
    {
        // Prepare / Mock
        $model = MapperModelTester::getNew(['userId' => 34]);
        $model->setId(45);

        $this->dependencies['databaseService']->expects($this->once())
            ->method('delete')
            ->with('MapperModelTester', ['id' => 45])
            ->willReturn(true);

        // Execute
        $result = $this->testObject->delete($model);

        // Assert Result
        $this->assertTrue($result);
    }

    /**
     * testGetDatabaseService Test that getDatabaseService executes as expected.
     */
    public function testGetDatabaseService()
    {
        // Execute
        $result = $this->testObject->getDatabaseService();

        // Assert Result
        $this->assertEquals($this->dependencies['databaseService'], $result);
    }

    /**
     * testGetTableFromClass Test that getTableFromClass executes as expected.
     */
    public function testGetTableFromClass()
    {
        // Prepare / Mock
        $class = MapperModelTester::class;

        // Execute
        $result = $this->testObject->getTableFromClass($class);

        // Assert Result
        $this->assertEquals('MapperModelTester', $result);
    }

    /**
     * testBindToModel Test that bindToModel executes as expected.
     */
    public function testBindToModelNoData()
    {
        // Prepare / Mock
        $class = MapperModelTester::class;
        $data = [];

        // Execute
        $result = $this->testObject->bindToModel($class, $data);

        // Assert Result
        $this->assertEquals([], $result);
    }

    /**
     * testBindToModel Test that bindToModel executes as expected.
     */
    public function testBindToModelWithData()
    {
        // Prepare / Mock
        $class = MapperModelTester::class;
        $data = [['id' => 45, 'name' => 'abdul', 'userId' => 47]];
        $expectedObject = MapperModelTester::getNew(['userId' => null]);
        $expectedObject->setId(45)
            ->setName('abdul')
            ->setUserId(47);

        // Execute
        $result = $this->testObject->bindToModel($class, $data);

        // Assert Result
        $this->assertEquals([$expectedObject], $result);
    }
}
