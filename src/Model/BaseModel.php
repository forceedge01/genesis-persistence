<?php

namespace Genesis\Services\Persistence\Model;

use Exception;
use Genesis\Services\Persistence\Contracts\ModelInterface;

abstract class BaseModel implements ModelInterface
{
    const METHOD_TYPE_GET = 'get';

    const METHOD_TYPE_SET = 'set';

    /**
     * Every modal inherits an Id property.
     */
    protected $id = 'integer PRIMARY KEY';

    /**
     * @var string
     */
    private $class;

    /**
     * Set the data on the object. Here be dragons, don't be alarmed by the amount of code below,
     * here is what it does:
     * - Get all properties defined on the object.
     * - Get the class name that has been instantiated.
     * - Set the required data on the object, throw an exception otherwise.
     * - Set the optional data on the object if provided.
     * - Calculate which properties have been set already, if any left assign null.
     * - Note: If a property is not defined but provided in the data, an exception will be thrown.
     *
     * @param array|[] $data
     */
    public function __construct(array $data = [])
    {
        $this->class = get_called_class();
        $properties = get_class_vars(get_called_class());

        $this->setRequiredData($data);
        $this->setOptionalData($data);

        // Don't format properties that are by the data.
        $properties = array_diff_key($properties, $data);
        unset($properties['class']);

        // All properties set to null.
        foreach ($properties as $property => $type) {
            $this->$property = null;
        }
    }

    /**
     * @param array|null $data
     *
     * @return Contracts\ModelInterface
     */
    public static function getNew(array $data = [])
    {
        return new static($data);
    }

    /**
     * If a getter or setter is called, dynamically set value if property exists.
     *
     * @param mixed $name
     */
    public function __call($name, array $args = [])
    {
        $property = $this->getProperty($name);

        if (strpos($name, self::METHOD_TYPE_GET) === 0) {
            return $this->$property;
        } elseif (strpos($name, self::METHOD_TYPE_SET) === 0) {
            $this->$property = $args[0];

            return $this;
        } else {
            throw new Exception("Invalid method, property '$property' not found on class '$this->class'");
        }
    }

    /**
     * Don't allow object to set properties that have not been defined, for self validating code.
     *
     * @param string $name
     */
    public function __set($name, $value)
    {
        throw new Exception("Property '$name' is not defined on the object, please check for a typo.");
    }

    /**
     * Don't allow properties that are not defined to be read, for self validating code.
     *
     * @param string $name
     */
    public function __get($name)
    {
        throw new Exception("Property '$name' not found on class '$this->class'");
    }

    /**
     * @param array $indexes
     * @param array $data
     */
    protected function setRequiredData(array $data)
    {
        $indexes = $this->getRequiredData();

        foreach ($indexes as $index) {
            if (! array_key_exists($index, $data)) {
                throw new Exception("Expected to have field '$index' on provided data.");
            }

            $property = lcfirst($index);
            $this->$property = $data[$index];
        }
    }

    /**
     * @param array $indexes
     * @param array $data
     */
    protected function setOptionalData(array $data)
    {
        $indexes = $this->getOptionalData();

        foreach ($indexes as $index) {
            if (isset($data[$index])) {
                $property = lcfirst($index);
                $this->$property = $data[$index];
            }
        }
    }

    /**
     * Set the required Data for the model, these have to be passed in during instantiation.
     *
     * @example [
     *          'name',
     *          'address'
     * ]
     *
     * @return array
     */
    abstract protected function getRequiredData();

    /**
     * Set the optional Data for the model, these may be passed in during instantiation.
     *
     * @example [
     *          'county',
     *          'address line 2'
     * ]
     *
     * @return array
     */
    abstract protected function getOptionalData();

    /**
     * Registered data types in the project.
     *
     * @return array
     */
    private function getDataTypes()
    {
        return [
            'int' => 0,
            'integer' => 0,
            'real' => 0,
            'text' => ''
        ];
    }

    /**
     * Get the property name from method name.
     *
     * @param mixed $name
     *
     * @return string
     */
    private function getProperty($name)
    {
        return lcfirst(str_replace([self::METHOD_TYPE_SET, self::METHOD_TYPE_GET], '', $name));
    }
}
