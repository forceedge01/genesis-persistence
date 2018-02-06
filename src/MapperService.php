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
    private static $reflections = [];

    public function __construct(Contracts\StoreInterface $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * @return Contracts\StoreInterface
     */
    public function getDatabaseService()
    {
        return $this->databaseService;
    }

    /**
     * @param string $class The class name.
     *
     * @return array
     */
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

        echo PHP_EOL;
        echo $query;

        return $this->databaseService->execute($query);
    }

    /**
     * Persists data to the chosen storage mechanism.
     *
     * @param Contracts\ModelInterface $object The model object.
     *
     * @return Contracts\ModelInterface
     */
    public function persist(Contracts\ModelInterface $object)
    {
        $class = get_class($object);
        $properties = $this->getPropertiesFromClass($class);

        $table = $this->getTableFromClass($class);
        $values = $this->getPropertiesValue($object, $properties);

        if (! empty($values['id'])) {
            return $this->databaseService->update($table, $values, ['id' => $values['id']]);
        }

        // If the id column is present and we are about to save this as a new record,
        // remove it so its not part of the sql query.
        unset($values['id']);

        $id = $this->databaseService->save($table, $values);
        $object->setId($id);

        return $object;
    }

    /**
     * @param string $class The class name.
     * @param array $where The criteria.
     * @param array $order The order of the results retrieved.
     *
     * @return Contracts\ModelInterface[]
     */
    public function get($class, array $where = [], array $order = ['id' => 'asc'])
    {
        if (! in_array(Contracts\ModelInterface::class, class_implements($class))) {
            throw new Exception("Invalid class given: '$class', must implement BaseModel!");
        }

        $table = $this->getTableFromClass($class);

        if ($where) {
            $data = $this->databaseService->get($table, $where, $order);
        } else {
            $data = $this->databaseService->getAll($table, $order);
        }

        return $this->bindToModel($class, $data);
    }

    /**
     * @param string $class The class name.
     *
     * @return Contracts\ModelInterface|false
     */
    public function getSingle($class, array $where = [], array $order = ['id' => 'asc'])
    {
        $table = $this->getTableFromClass($class);
        $data = $this->databaseService->getSingle($table, $where, $order);

        if ($data) {
            $collection = $this->bindToModel($class, [$data]);

            return $collection[0];
        }

        return false;
    }

    /**
     * @param string $class The class to fetch the count for.
     * @param array $where The criteria.
     *
     * @return int
     */
    public function getCount($class, array $where)
    {
        $table = $this->getTableFromClass($class);
        $data = $this->databaseService->getCount($table, $where);

        return $data[0]["{$table}Count"];
    }

    /**
     * @param string $associatedClass The associated class.
     * @param Contracts\ModelInterface $fromObject The association object.
     *
     * @return Contracts\ModelInterface|false
     */
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

    /**
     * @param string $class The class name.
     * @param array $where The criteria.
     *
     * @return Contracts\StoreInterface
     */
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

    /**
     * @param string $class The class name.
     *
     * @return string
     */
    public function getTableFromClass($class)
    {
        $chunks = explode('\\', $class);

        return end($chunks);
    }

    /**
     * @param string $class The class name.
     * @param array $data The data to bind.
     *
     * @return Contracts\ModelInterface[]
     */
    public function bindToModel($class, array $data)
    {
        if (! in_array(Contracts\ModelInterface::class, class_implements($class))) {
            throw new Exception("Class '$class' does not implmenet interface " . Contracts\ModelInterface::class);
        }

        if (! $data) {
            return [];
        }

        $collection = [];

        foreach ($data as $record) {
            $object = $class::getNew($record);
            $collection[] = $object;
        }

        return $collection;
    }

    /**
     * Get all default values for a class.
     *
     * @param string $class The class.
     *
     * @return array
     */
    public function getPropertiesWithTypesFromClass($class)
    {
        return $this->getReflection($class)->getDefaultProperties();
    }

    /**
     * Get all properties from a class.
     *
     * @param string $class The class.
     *
     * @return array
     */
    private function getPropertiesFromClass($class)
    {
        return array_keys($this->getReflection($class)->getDefaultProperties());
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

    private function getReflection($class)
    {
        if (! isset(self::$reflections[$class])) {
            self::$reflections[$class] = new ReflectionClass($class);
        }

        return self::$reflections[$class];
    }
}
