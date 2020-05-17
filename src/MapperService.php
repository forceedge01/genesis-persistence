<?php

namespace Genesis\Services\Persistence;

use Exception;
use Genesis\Services\Persistence\Contracts\ModelInterface;
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
    public function getDatabaseService(): Contracts\StoreInterface
    {
        return $this->databaseService;
    }

    /**
     * @param string $class The class name.
     *
     * @return array
     */
    public function createTable($class): array
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
    public function persist(Contracts\ModelInterface $object): Contracts\ModelInterface
    {
        $properties = $this->getPropertiesFromClass($object);

        $class = get_class($object);
        $table = $this->getTableFromClass($class);
        $values = $this->getPropertiesValue($object, $properties);

        if (property_exists($object->data, $object::$primaryKey)) {
            $this->databaseService->update(
                $table,
                $values,
                [$object::$primaryKey => $object->data->{$object::$primaryKey}]
            );

            return $object;
        }

        $id = $this->databaseService->save($table, $values);
        $method = 'set' . ucfirst($object::$primaryKey);
        $object->$method($id);

        return $object;
    }

    /**
     * @param string $class The class name.
     * @param array  $where The criteria.
     * @param array  $order The order of the results retrieved.
     *
     * @return Contracts\ModelInterface[]
     */
    public function get($class, array $where = [], array $order = ['id' => 'asc'], int $limit = null): array
    {
        if (! in_array(Contracts\ModelInterface::class, class_implements($class))) {
            throw new Exception("Invalid class given: '$class', must implement BaseModel!");
        }

        $table = $this->getTableFromClass($class);

        if ($where) {
            $data = $this->databaseService->get($table, $where, $order, $limit);
        } else {
            $data = $this->databaseService->getAll($table, $order, $limit);
        }

        return $this->bindToModel($class, $data);
    }

    /**
     * @param string $class The class name.
     *
     * @return Contracts\ModelInterface|null
     */
    public function getSingle($class, array $where = [], array $order = ['id' => 'asc']): ?Contracts\ModelInterface
    {
        $table = $this->getTableFromClass($class);
        $data = $this->databaseService->getSingle($table, $where, $order, 1);

        if ($data !== null) {
            $collection = $this->bindToModel($class, [$data]);

            return $collection[0];
        }

        return null;
    }

    /**
     * @param string $class The class to fetch the count for.
     * @param array  $where The criteria.
     *
     * @return int
     */
    public function getCount($class, array $where = []): int
    {
        $table = $this->getTableFromClass($class);
        $data = $this->databaseService->getCount($table, $class::$primaryKey, $where);

        return $data[0]["{$table}Count"];
    }

    /**
     * @param string                   $associatedClass The associated class.
     * @param Contracts\ModelInterface $fromObject      The association object.
     *
     * @return Contracts\ModelInterface|false
     */
    public function getAssociated($associatedClass, Contracts\ModelInterface $fromObject): ?Contracts\ModelInterface
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
     * @param array  $where The criteria.
     *
     * @return Contracts\StoreInterface
     */
    public function delete($class, array $where = []): self
    {
        if (is_object($class)) {
            $obj = $class;
            $class = get_class($class);

            if (!property_exists($obj->data, $class::$primaryKey)) {
                throw new Exception("Object of type '$class' passed in must have an id to perform operation.");
            }

            $id = $obj->data->{$class::$primaryKey};
            $where = [$class::$primaryKey => $id];
        }

        $table = $this->getTableFromClass($class);
        $this->databaseService->delete($table, $where);

        return $this;
    }

    public function deleteById(string $class, $id): self
    {
        $this->delete($class, [$class::$primaryKey => $id]);

        return $this;
    }

    /**
     * @param string $class The class name.
     *
     * @return string
     */
    public function getTableFromClass($class): string
    {
        if (! is_object($class) && ! is_string($class)) {
            throw new Exception('Operation can only be performed on class string or object.');
        }

        if (is_object($class)) {
            $class = get_class($class);
        }

        if (property_exists($class, 'table')) {
            return $class::$table;
        }

        $chunks = explode('\\', $class);

        return end($chunks);
    }

    /**
     * @param string $class The class name.
     * @param array  $data  The data to bind.
     *
     * @return Contracts\ModelInterface[]
     */
    public function bindToModel(string $class, array $data): array
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
    public function getPropertiesWithTypesFromClass(string $class): array
    {
        return $this->getReflection($class)->getDefaultProperties();
    }

    /**
     * Get all properties from a class.
     */
    private function getPropertiesFromClass(ModelInterface $object): array
    {
        return get_object_vars($object->data);
    }

    private function getPropertiesValue(Contracts\ModelInterface $object, array $properties): array
    {
        return (array) $object->data;
    }

    private function getReflection($class)
    {
        if (! isset(self::$reflections[$class])) {
            self::$reflections[$class] = new ReflectionClass($class);
        }

        return self::$reflections[$class];
    }
}
