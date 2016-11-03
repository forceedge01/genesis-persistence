<?php

namespace Genesis\Services\Test\Persistence\Model;

use Genesis\Services\Persistence\Model\BaseModel;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

class BaseModelTester extends BaseModel
{
    protected $userId = 'int not null';

    protected $name = 'text not null';
}

class BaseModelTest extends PHPUnit_Framework_TestCase
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
            'Name of dependency' => null, //nmock
        ];

        $this->reflection = new ReflectionClass(BaseModelTester::class);
        $this->testObject = $this->reflection->newInstanceArgs($this->dependencies);
    }

    /**
     * test Test that Construct executes as expected.
     */
    public function testConstructSetsDefaultValuesAndAccessibleByMagicGetters()
    {
        // Assert Result
        $this->assertEquals(0, $this->testObject->getId());
        $this->assertEquals(0, $this->testObject->getUserId());
        $this->assertEquals('', $this->testObject->getName());
    }

    /**
     * testMagicGettersAndSetters Test that MagicGettersAndSetters executes as expected.
     */
    public function testMagicGettersAndSetters()
    {
        // Execute
        $this->testObject->setId(5);
        $this->testObject->setName('Abdul');
        $this->testObject->setUserId(25);

        // Assert
        $this->assertEquals(5, $this->testObject->getId());
        $this->assertEquals('Abdul', $this->testObject->getName());
        $this->assertEquals(25, $this->testObject->getUserId());
    }

    /**
     * testMagicGettersAndSetters Test that MagicGettersAndSetters executes as expected.
     *
     * @expectedException Exception
     */
    public function testMagicGetterThrowExceptionSet()
    {
        // Execute
        $this->testObject->setRandomProperty(5);
    }

    /**
     * testMagicGettersAndSetters Test that MagicGettersAndSetters executes as expected.
     *
     * @expectedException Exception
     */
    public function testMagicGetterThrowExceptionGet()
    {
        // Execute
        $this->testObject->getRandomProperty();
    }

    /**
     * testMagicGettersAndSetters Test that MagicGettersAndSetters executes as expected.
     *
     * @expectedException Exception
     */
    public function testMagicGetterThrowExceptionNonExistantMethod()
    {
        // Execute
        $this->testObject->zanzibar();
    }

    /**
     * testMagicGettersAndSetters Test that MagicGettersAndSetters executes as expected.
     *
     * @expectedException Exception
     */
    public function testMagicGetterThrowExceptionPropertyMethod()
    {
        // Execute
        $this->testObject->name();
    }
}
