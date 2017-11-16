<?php

namespace Genesis\Services\Test\Persistence;

use Genesis\Services\Persistence\DatabaseService;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

class DatabaseServiceTest extends PHPUnit_Framework_TestCase
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
            ['dbengine' => 'sqlite', 'path' => ':memory:']
        ];

        $this->reflection = new ReflectionClass(DatabaseService::class);
        $this->testObject = $this->reflection->newInstanceArgs($this->dependencies);
    }

    public function testSave()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testExecute()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testGet()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testGetAll()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testGetCount()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testGetSingle()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testDelete()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testUpdate()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testGetOrderClause()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testGetWhereClauseFromArray()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testGetUpdateClauseFromArray()
    {
        $this->markTestIncomplete('Needs implementing');
    }

    public function testGetValuesClauseFromArray()
    {
        $this->markTestIncomplete('Needs implementing');
    }
}
