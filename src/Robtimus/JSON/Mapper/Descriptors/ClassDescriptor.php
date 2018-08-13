<?php
namespace Robtimus\JSON\Mapper\Descriptors;

use ReflectionClass;
use Robtimus\JSON\Mapper\JSONMappingException;
use Robtimus\JSON\Mapper\TypeHelper;

/**
 * A descriptor for a single JSON serializable class.
 */
final class ClassDescriptor {

    /**
     * @var ReflectionClass
     */
    private $class;

    /**
     * @var array[string][PropertyDescriptor]
     */
    private $properties = array();

    /**
     * @var callable[]
     */
    private $anyGetters = array();

    /**
     * @var callable|null
     */
    private $anySetter = null;

    /**
     * @param ReflectionClass $class
     */
    public function __construct(ReflectionClass $class) {
        $this->class = $class;
    }

    /**
     * @param ClassDescriptor $descriptor
     * @param ReflectionClass $class
     * @return ClassDescriptor A copy of the given class descriptor
     */
    public static function copy(ClassDescriptor $descriptor, ReflectionClass $class) {
        $copy = new ClassDescriptor($class);
        foreach ($descriptor->properties as $name => $property) {
            $copy->properties[$name] = PropertyDescriptor::copy($property);
        }
        foreach ($descriptor->anyGetters as $anyGetter) {
            $copy->anyGetters[] = $anyGetter;
        }
        $copy->anySetter = $descriptor->anySetter;
        return $copy;
    }

    /**
     * Adds a new property, possibly overwriting any existing property with the same name
     * @param string $name The property name
     * @param ReflectionClass|string $type The property type
     * @param ReflectionClass|null $relativeTo An optional class that the given type is relative to
     * @return PropertyDescriptor The added property descriptor
     */
    public function addProperty($name, $type, ReflectionClass $relativeTo = null) {
        $property = PropertyDescriptor::create($type, $relativeTo);
        $this->properties[$name] = $property;
        return $property;
    }

    /**
     * Ensures that a property exists, adding it if necessary
     * @param string $name The property name
     * @param ReflectionClass|string $type The property type
     * @param ReflectionClass|null $relativeTo An optional class that the given type is relative to
     * @return PropertyDescriptor The existing or added property descriptor
     * @throws JSONMappingException If the property existed with a different type
     */
    public function ensureProperty($name, $type, ReflectionClass $relativeTo = null) {
        if (array_key_exists($name, $this->properties)) {
            $property = $this->properties[$name];
            $type = TypeHelper::normalizeType($type, $relativeTo);
            if ($type !== $property->type()) {
                $className = $this->class->getName();
                throw new JSONMappingException("Found different types for property '$name' of class $className");
            }
            return $property;
        }
        return $this->addProperty($name, $type, $relativeTo);
    }

    /**
     * Changes the property order
     * @param string[]|null $propertyNames An array containing the property names in the desired order, or null to sort alphabetically
     * @return $this
     */
    public function orderProperties(&$propertyNames = null) {
        if (is_null($propertyNames)) {
            ksort($this->properties);
        } else {
            if (!$this->containsAllPropertyNames($propertyNames)) {
                $className = $this->class->getName();
                $namesString = implode(',', array_keys($this->properties));
                $propertyNamesString = implode(',', $propertyNames);
                throw new JSONMappingException("Properties must include all properties for class $className; expected: $namesString, given: $propertyNamesString");
            }

            $comparator = function($a, $b) use ($propertyNames) {
                $ia = array_search($a, $propertyNames, true);
                $ib = array_search($b, $propertyNames, true);
                return $ia < $ib ? -1 : ($ia > $b ? 1 : 0);
            };
            uksort($this->properties, $comparator);
        }
        return $this;
    }

    private function containsAllPropertyNames(&$propertyNames) {
        if (count($this->properties) !== count($propertyNames)) {
            return false;
        }
        foreach ($propertyNames as $name) {
            if (!array_key_exists($name, $this->properties)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param callable A function that takes an object and returns an associative array with additional properties for this object
     * @return ClassDescriptor $this
     */
    public function withAnyGetter(callable $anyGetter) {
        $this->anyGetters[] = $anyGetter;
        return $this;
    }

    /**
     * @param callable|null $anySetter A function that takes an object and the name and value of an unknown property and sets this property on this object
     * @return ClassDescriptor $this
     */
    public function withAnySetter(callable $anySetter = null) {
        $this->anySetter = $anySetter;
        return $this;
    }

    /**
     * @return string
     * @internal
     */
    public function className() {
        return $this->class->getName();
    }

    /**
     * @return array[string][PropertyDescriptor]
     * @internal
     */
    public function properties() {
        return $this->properties;
    }

    /**
     * @return callable[]
     * @internal
     */
    public function anyGetters() {
        return $this->anyGetters;
    }

    /**
     * @return callable|null
     * @internal
     */
    public function anySetter() {
        return $this->anySetter;
    }
}
