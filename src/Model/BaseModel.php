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

    public function __construct()
    {
        $properties = get_class_vars(get_called_class());

        // All properties set to null.
        foreach ($properties as $property => $type) {
            $chunks = explode(' ', $type);
            $dataTypes = $this->getDataTypes();

            if (isset($dataTypes[$chunks[0]])) {
                $this->$property = $dataTypes[$chunks[0]];
            }
        }
    }

    /**
     * @return Contracts\ModelInterface
     */
    public static function getNew()
    {
        return new static();
    }

    /**
     * If a getter or setter is called, dynamically set value if property exists.
     *
     * @param mixed $name
     */
    public function __call($name, array $args = [])
    {
        $property = $this->getProperty($name);
        $methodType = $this->getMethodType($name);
        $class = get_called_class();

        if (property_exists($class, $property)) {
            if ($methodType == self::METHOD_TYPE_GET) {
                return $this->$property;
            } elseif ($methodType == self::METHOD_TYPE_SET) {
                $this->$property = $args[0];

                return $this;
            }
            throw new Exception("Invalid method on '$property' on class '$class'");
        }

        throw new Exception("Property '$property' not found on class '$class'");
    }

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

    /**
     * Get the method type or false if not found. Method types are get, set.
     *
     * @param string $name
     *
     * @return string|false
     */
    private function getMethodType($name)
    {
        if (strpos($name, self::METHOD_TYPE_GET) === 0) {
            return self::METHOD_TYPE_GET;
        } elseif (strpos($name, self::METHOD_TYPE_SET) === 0) {
            return self::METHOD_TYPE_SET;
        }

        return false;
    }
}
