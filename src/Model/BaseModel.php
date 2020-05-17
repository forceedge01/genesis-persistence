<?php

namespace Genesis\Services\Persistence\Model;

use DateTime;
use Exception;
use Genesis\Services\Persistence\Contracts\ModelInterface;

/**
 * The following is inherited by an extending class:
 * - An Id property is inherited for all models.
 * - The extending class must define required data fields that it expects through the constructor.
 * - The data passed into the constructor is set automatically and efficiently.
 * - Any excess data which does not match the properties on the model throws an exception.
 * - Any property name that ends with Date is cast to a DateTime object.
 */
abstract class BaseModel implements ModelInterface
{
    const METHOD_TYPE_GET = 'get';

    const METHOD_TYPE_SET = 'set';

    const FORMAT_DATE = 'Date';

    public $data;

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
    private function __construct(array $data = [])
    {
        $this->class = get_called_class();
        $this->data = new \stdClass();
        $this->setData($data);
    }

    public function getId()
    {
        return $this->data->{static::$primaryKey};
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
            return $this->data->$property;
        } elseif (strpos($name, self::METHOD_TYPE_SET) === 0) {
            $this->setValue($property, $args[0]);
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
     * Set data on the object as specified.
     *
     * @param array $data
     */
    protected function setData(array $data)
    {
        $this->setRequiredData($data);
        $this->setOptionalData($data);
    }

    /**
     * @param array $indexes
     * @param array $data
     */
    protected function setRequiredData(array $data)
    {
        $indexes = $this->getRequiredFields();

        foreach ($indexes as $index) {
            if (! array_key_exists($index, $data)) {
                throw new Exception("Expected to have field '$index' on provided data.");
            }

            $this->setValue(lcfirst($index), $data[$index]);
        }

        return $indexes;
    }

    /**
     * @param array $data
     */
    protected function setOptionalData(array $data)
    {
        foreach ($data as $property => $value) {
            $this->setValue($property, $value);
        }
    }

    /**
     * Set the required Data for the model, these have to be passed in during instantiation.
     * Any properties not returned are assumed optional.
     *
     * @example [
     *     'name',
     *     'address'
     * ]
     *
     * @return array
     */
    protected function getRequiredFields(): array
    {
        return [];
    }

    /**
     * @param string $property
     * @param string $value
     */
    public function setValue($property, $value): self
    {
        if (strpos($property, self::FORMAT_DATE) > 0 && $value) {
            $this->data->$property = new DateTime($value);

            return $this;
        }

        $this->data->$property = $value;

        return $this;
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
}
