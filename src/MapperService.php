<?php

namespace Genesis\Services\Persistence;

use Exception;
use ReflectionClass;

/**
 * MapperService class.
 */
class MapperService implements Contracts\MapperInterface
{
    private $databaseService;

    public function __construct(Contracts\StoreInterface $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    public function createTable($class)
    {
        $table = $this->getTableFromClass($class);
        $properties = $this->getPropertiesWithTypesFromClass($class);

        $query = "CREATE TABLE IF NOT EXISTS `$table` (";

        foreach ($properties as $property => $type) {
            $query .= "`$property` $type, ";
        }

        $query = rtrim($query, ', ');
        $query .= ')';

        $this->databaseService->execute($query);
    }

    public function delete($class, array $where = [])
    {
        $table = $this->getTableFromClass($class);

        return $this->databaseService->delete($table, $where);
    }

    /**
     * set.
     *
     * @param string $key [description]
     *
     * @return $this
     */
    public function persist($object)
    {
        $class = get_class($object);

        $properties = $this->getPropertiesFromClass($object);

        $table = $this->getTableFromClass($class);
        $values = $this->getPropertiesValue($object, $properties);

        if (!empty($values['id'])) {
            $this->databaseService->update($table, $values, ['id' => $values['id']]);
        }

        // If the id column is present and we are about to save this as a new record,
        // remove it so its not part of the sql query.
        unset($values['id']);

        $id = $this->databaseService->save($table, $values);
        $object->setId($id);

        return $object;
    }

    public function get($class, array $args = [])
    {
        if (! in_array(Contracts\ModelInterface::class, class_implements($class))) {
            throw new Exception("Invalid class given: '$class', must implement BaseModel!");
        }

        $table = $this->getTableFromClass($class);

        if ($args) {
            $data = $this->databaseService->get($table, $args);
        } else {
            $data = $this->databaseService->getAll($table);
        }

        return $this->bindToObject($class, $data);
    }

    public function getSingle($class, array $args = [], $order = 'asc')
    {
        $table = $this->getTableFromClass($class);
        $data = $this->databaseService->getSingle($table, $args, $order);

        if ($data) {
            $collection = $this->bindToObject($class, [$data]);

            return $collection[0];
        }

        return false;
    }

    private function getPropertiesFromClass($class)
    {
        $reflection = new ReflectionClass($class);

        return array_keys($reflection->getDefaultProperties());
    }

    private function getPropertiesWithTypesFromClass($class)
    {
        $reflection = new ReflectionClass($class);

        return $reflection->getDefaultProperties();
    }

    private function getTableFromClass($class)
    {
        $chunks = explode('\\', $class);

        return end($chunks);
    }

    private function getPropertiesValue($object, array $properties)
    {
        $values = [];
        foreach ($properties as $property) {
            $call = 'get' . ucfirst($property);
            $values[$property] = $object->$call();
        }

        return $values;
    }

    private function setObjectPropertyValues($object, array $properties)
    {
        foreach ($properties as $property => $value) {
            $call = 'set' . ucfirst($property);
            $object->$call($value);
        }

        return $object;
    }

    private function bindToObject($class, array $data)
    {
        if (! $data) {
            return [];
        }

        $collection = [];

        foreach ($data as $record) {
            $object = new $class();
            $this->setObjectPropertyValues($object, $record);
            $collection[] = $object;
        }

        return $collection;
    }
}
