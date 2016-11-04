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

    public function getDatabaseService()
    {
        return $this->databaseService;
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

        return $this->databaseService->execute($query);
    }

    /**
     * set.
     *
     * @param string $key [description]
     *
     * @return $this
     */
    public function persist(Contracts\ModelInterface $object)
    {
        $class = get_class($object);

        $properties = $this->getPropertiesFromClass($object);

        $table = $this->getTableFromClass($class);
        $values = $this->getPropertiesValue($object, $properties);

        if (!empty($values['id'])) {
            return $this->databaseService->update($table, $values, ['id' => $values['id']]);
        }

        // If the id column is present and we are about to save this as a new record,
        // remove it so its not part of the sql query.
        unset($values['id']);

        $id = $this->databaseService->save($table, $values);
        $object->setId($id);

        return $object;
    }

    public function get($class, array $args = [], array $order = ['id' => 'asc'])
    {
        if (! in_array(Contracts\ModelInterface::class, class_implements($class))) {
            throw new Exception("Invalid class given: '$class', must implement BaseModel!");
        }

        $table = $this->getTableFromClass($class);

        if ($args) {
            $data = $this->databaseService->get($table, $args, $order);
        } else {
            $data = $this->databaseService->getAll($table, $order);
        }

        return $this->bindToModel($class, $data);
    }

    public function getSingle($class, array $args = [], array $order = ['id' => 'asc'])
    {
        $table = $this->getTableFromClass($class);
        $data = $this->databaseService->getSingle($table, $args, $order);

        if ($data) {
            $collection = $this->bindToModel($class, [$data]);

            return $collection[0];
        }

        return false;
    }

    public function getAssociated($associatedClass, Contracts\ModelInterface $fromObject)
    {
        // Check if the associated class has a property on the fromObject.
        $table = $this->getTableFromClass($associatedClass);
        $tableProperty = lcfirst($table);
        $associatedProperty = $tableProperty . 'Id';

        if (! property_exists($fromObject, $associatedProperty)) {
            throw new Exception(sprintf(
                'Property "%s" does not exist on class "%s" and is not associated.',
                $associatedProperty,
                get_class($fromObject)
            ));
        }

        $associatedPropertyMethod = 'get' . $table . 'Id';

        return $this->getSingle(
            $associatedClass,
            ['id' => $fromObject->$associatedPropertyMethod()]
        );
    }

    public function delete($class, array $where = [])
    {
        if (is_object($class)) {
            $obj = $class;
            $class = get_class($obj);
            $id = $obj->getId();

            if (! $id) {
                throw new Exception("Object of type '$class' passed in must have an id to perform operation.");
            }

            $where = ['id' => $id];
        }

        $table = $this->getTableFromClass($class);

        return $this->databaseService->delete($table, $where);
    }

    public function getTableFromClass($class)
    {
        $chunks = explode('\\', $class);

        return end($chunks);
    }

    public function bindToModel($class, array $data)
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

    private function getPropertiesValue(Contracts\ModelInterface $object, array $properties)
    {
        $values = [];
        foreach ($properties as $property) {
            $call = 'get' . ucfirst($property);
            $values[$property] = $object->$call();
        }

        return $values;
    }

    private function setObjectPropertyValues(Contracts\ModelInterface $object, array $properties)
    {
        foreach ($properties as $property => $value) {
            $call = 'set' . ucfirst($property);
            $object->$call($value);
        }

        return $object;
    }
}
