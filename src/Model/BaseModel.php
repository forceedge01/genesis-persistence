<?php

namespace Genesis\Services\Persistence\Model;

use Exception;
use Genesis\Services\Persistence\Contracts\ModelInterface;

abstract class BaseModel implements ModelInterface
{
    protected $id = 'integer PRIMARY KEY';

    public function __construct()
    {
        $properties = get_class_vars(get_called_class());

        // All properties set to null.
        foreach ($properties as $property => $type) {
            $chunks = explode(' ', $type);
            switch (strtolower($chunks[0])) {
                case 'int':
                case 'integer':
                case 'real':
                    $this->$property = 0;
                    break;
                case 'text':
                    $this->$property = '';
                    break;
            }
        }
    }

    /**
     * If a getter or setter is called, dynamically set value if property exists.
     */
    public function __call($name, array $args = [])
    {
        $property = $this->getProperty($name);
        $methodType = $this->getMethodType($name);
        $class = get_called_class();

        if (property_exists($class, $property)) {
            if ($methodType == 'get') {
                return $this->$property;
            } elseif ($methodType == 'set') {
                $this->$property = $args[0];

                return $this;
            } else {
                throw new Exception("Invalid method on '$property' on class '$class'");
            }
        }

        throw new Exception("Property '$property' not found on class '$class'");
    }

    private function getProperty($name)
    {
        return lcfirst(str_replace(['get', 'set'], '', $name));
    }

    private function getMethodType($name)
    {
        if (strpos($name, 'get') === 0) {
            return 'get';
        } elseif (strpos($name, 'set') === 0) {
            return 'set';
        }

        return false;
    }
}
