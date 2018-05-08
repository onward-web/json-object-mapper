<?php
namespace Robtimus\JSON\Mapper;

use stdClass;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Robtimus\JSON\Mapper\Descriptors\ClassDescriptor;
use Robtimus\JSON\Mapper\Descriptors\PropertyDescriptor;

/**
 * ObjectMapper provides functionality for converting objects to and from JSON.
 */
class ObjectMapper {

    private static $ACCESSOR_TYPE_METHOD        = 'METHOD';
    private static $ACCESSOR_TYPE_PROPERTY      = 'PROPERTY';
    private static $ACCESSOR_TYPE_PUBLIC_MEMBER = 'PUBLIC_MEMBER';
    private static $ACCESSOR_TYPE_NONE          = 'NONE';

    /**
     * The class descriptors per fully-qualified class name
     * @var array[string][ClassDescriptor]
     */
    private $classes = array();

    /**
     * A cache for instances, indexed by fully-qualified class names
     * @var array[string][object]
     */
    private $instanceCache = array();

    /**
     * The default serializers per type.
     * @var array[string][JSONSerializer]
     */
    private $defaultSerializers = array();

    /**
     * The default deserializers per type.
     * @var array[string][JSONDeserializer]
     */
    private $defaultDeserializers = array();

    /*********************/
    /* Public properties */
    /*********************/

    /**
     * If false, throw an exception if an undefined property is encountered in JSON. Ignored when formatting.
     * Default value: false
     * @var bool
     */
    public $allowUndefinedProperties = false;

    /**
     * If true, null values are omitted from generated JSON. Ignored when parsing.
     * Default value: false
     * @var bool
     */
    public $omitNullValues = false;

    /**
     * If true, empty arrays are omitted from generated JSON. Ignored when parsing.
     * Default value: false
     * @var bool
     */
    public $omitEmptyArrays = false;

    /***************************************/
    /* default serializers / deserializers */
    /***************************************/

    /**
     * Sets the default JSON serializer for the given type.
     * This will be used for the type unless a JSON serializer is defined for a property.
     * If the given JSON serializer is null, then the default will be unregistered instead.
     * @param ReflectionClass|string $type
     * @param JSONSerializer|null $serializer
     */
    public function setDefaultSerializer($type, JSONSerializer $serializer = null) {
        $type = TypeHelper::normalizeType($type);

        if (!is_null($serializer)) {
            $this->defaultSerializers[$type] = $serializer;
        } else if (array_key_exists($type, $this->defaultSerializers)) {
            unset($this->defaultSerializers[$type]);
        }
    }

    /**
     * Sets the default JSON deserializer for the given type.
     * This will be used for the type unless a JSON deserializer is defined for a property.
     * If the given JSON deserializer is null, then the default will be unregistered instead.
     * @param ReflectionClass|string $type
     * @param JSONDeserializer|null $deserializer
     */
    public function setDefaultDeserializer($type, JSONDeserializer $deserializer = null) {
        $type = TypeHelper::normalizeType($type);

        if (!is_null($deserializer)) {
            $this->defaultDeserializers[$type] = $deserializer;
        } else if (array_key_exists($type, $this->defaultDeserializers)) {
            unset($this->defaultDeserializers[$type]);
        }
    }

    /***********************/
    /* Manual registration */
    /***********************/

    /**
     * Registers a class descriptor, to prevent its class from being automatically inspected
     * @param ClassDescriptor $classDescriptor
     */
    public function registerClass(ClassDescriptor $classDescriptor) {
        $this->classes[$classDescriptor->className()] = $classDescriptor;
    }

    /******************/
    /* object -> JSON */
    /******************/

    /**
     * Converts the given object to JSON.
     * @param object|array object The object or array to convert.
     * @param int options The serialization options. See <a href="http://php.net/manual/en/function.json-encode.php">json_encode</a> for more information.
     * @return string
     * @throws InvalidArgumentException If the argument is not an object, or if it's a stdClass
     * @throws JSONMappingException If the class could not be processed correctly.
     * @throws JSONGenerationException If the class could not be converted to JSON.
     */
    public function toJSON($object, $options = 0) {
        if (!is_array($object) && !is_object($object)) {
            throw new InvalidArgumentException('Only objects and arrays are supported');
        }

        // to prevent loops
        $converted = array();

        if (is_array($object) || is_a($object, 'stdClass')) {
            $jsonObject = $this->toJSONValue($object, $converted);
        } else {
            $jsonObject = $this->toJSONObject($object, $converted);
        }

        $result = json_encode($jsonObject, $options);
        if ($result === false) {
            throw JSONGenerationException::fromLastJSONError();
        }
        return $result;
    }

    private function toJSONObject($object, &$converted) {
        $class = new ReflectionClass($object);
        $className = $class->getName();
        if (!array_key_exists($className, $this->classes)) {
            $this->inspectClass($class);
        }
        if (in_array($object, $converted, true)) {
            throw new JSONGenerationException('Recursion detected');
        }

        $converted[] = $object;

        $jsonObject = new stdClass();

        $classDescriptor = $this->classes[$className];

        $this->addPropertiesToJSONObject($object, $classDescriptor, $jsonObject, $converted);
        $this->addPropertiesFromAnyGetters($object, $classDescriptor, $jsonObject, $converted);

        return $jsonObject;
    }

    private function addPropertiesToJSONObject($object, ClassDescriptor $classDescriptor, stdClass $jsonObject, &$converted) {
        foreach ($classDescriptor->properties() as $name => $property) {
            $getter = $property->getter();
            if (!is_null($getter)) {
                $value = $getter($object);

                $serializer = $property->serializer();
                if (is_null($serializer)){
                    $type = $property->type();
                    if (array_key_exists($type, $this->defaultSerializers)) {
                        $value = $this->defaultSerializers[$type]->toJSON($value);
                    } else {
                        $value = $this->toJSONValue($value, $converted);
                    }
                } else {
                    $value = $serializer->toJSON($value);
                }
                if (!$this->omitValue($value)) {
                    $jsonObject->{$name} = $value;
                }
            }
        }
    }

    private function addPropertiesFromAnyGetters($object, ClassDescriptor $classDescriptor, stdClass $jsonObject, &$converted) {
        foreach ($classDescriptor->anyGetters() as $anyGetter) {
            foreach ($anyGetter($object) as $name => $value) {
                $value = $this->toJSONValue($value, $converted);
                if (!$this->omitValue($value)) {
                    $jsonObject->{$name} = $value;
                }
            }
        }
    }

    private function omitValue(&$value) {
        return (is_null($value) && $this->omitNullValues)
            || (is_array($value) && count($value) === 0 && $this->omitEmptyArrays);
    }

    private function toJSONValue(&$value, &$converted) {
        if (is_array($value)) {
            $jsonValue = array();
            foreach ($value as $val) {
                $jsonValue[] = $this->toJSONValue($val, $converted);
            }
            return $jsonValue;
        }
        if (is_object($value)) {
            if (is_a($value, '\stdClass')) {
                $jsonValue = new stdClass();
                foreach ($value as $name => $val) {
                    $jsonValue->{$name} = $this->toJSONValue($val, $converted);
                }
                return $jsonValue;
            }
            return $this->toJSONObject($value, $converted);
        }
        return $value;
    }

    /******************/
    /* JSON -> object */
    /******************/

    /**
     * Converts the given JSON string to an object.
     * @param string $json The JSON string to convert.
     * @param \ReflectionClass|string the class to convert to. If the JSON string represents an array this must be the element type.
     * @return object
     * @throws JSONMappingException If the class could not be processed correctly.
     * @throws JSONParseException If the class could not be converted to JSON.
     */
    public function fromJSON($json, $class) {
        if (is_string($class)) {
            $class = new ReflectionClass($class);
        }
        if (!is_object($class) || !is_a($class, '\ReflectionClass')) {
            throw new InvalidArgumentException('class must be a class name or ReflectionClass');
        }

        $jsonObject = json_decode($json);
        if (is_null($jsonObject) || $jsonObject === false) {
            throw JSONParseException::fromLastJSONError();
        }
        if (is_array($jsonObject)) {
            return $this->fromJSONValue($jsonObject, $class->getName() . '[]');
        }

        return $this->fromJSONObject($jsonObject, $class);
    }

    private function fromJSONObject(stdClass $jsonObject, ReflectionClass $class) {
        if (!array_key_exists($class->getName(), $this->classes)) {
            $this->inspectClass($class);
        }

        $className = $class->getName();
        $classDescriptor = $this->classes[$className];

        $object = $this->createInstance($class);

        foreach ($jsonObject as $name => $val) {
            $properties = $classDescriptor->properties();

            $setter = array_key_exists($name, $properties) ? $properties[$name]->setter() : null;
            $anySetter = $classDescriptor->anySetter();
            if (!is_null($setter)) {
                $type = $properties[$name]->type();
                $deserializer = $properties[$name]->deserializer();
                if (is_null($deserializer)) {
                    if (array_key_exists($type, $this->defaultDeserializers)) {
                        $value = $this->defaultDeserializers[$type]->fromJSON($val, $type);
                    } else {
                        $value = $this->fromJSONValue($val, $type);
                    }
                } else {
                    $value = $deserializer->fromJSON($val, $type);
                }
                $setter($object, $value);
            } else if (!is_null($anySetter)) {
                $anySetter($object, $name, $val);
            } else if (!$this->allowUndefinedProperties) {
                throw new JSONParseException("Undefined property for class $className: $name");
            }
        }

        return $object;
    }

    private function fromJSONValue(&$value, $type) {
        if (is_null($value)) {
            return $value;
        }

        $value = $this->validateAndNormalizeType($value, $type);
        if (is_array($value)) {
            $elementType = substr($type, 0, -2);
            $array = array();
            foreach ($value as $val) {
                $val = $this->validateAndNormalizeType($val, $elementType);
                $array[] = $this->fromJSONValue($val, $elementType);
            }
            return $array;
        }
        if (is_object($value)) {
            // must be stdClass
            return $this->fromJSONObject($value, new ReflectionClass($type));
        }
        return $value;
    }

    private function validateAndNormalizeType(&$value, $type) {
        if (substr($type, -2) === '[]') {
            $this->validateIsArray($value, $type);
            return $value;
        }
        if (class_exists($type)) {
            $this->validateIsObject($value, $type);
            return $value;
        }
        if ($type === 'boolean' || $type === 'bool') {
            return $this->validateIsBoolean($value);
        }
        if ($type === 'integer' || $type === 'int') {
            return $this->validateIsInt($value);
        }
        if ($type === 'float' || $type === 'double') {
            return $this->validateIsDouble($value);
        }
        if ($type === 'string') {
            return $this->validateIsString($value);
        }
        throw new JSONParseException("Unsupported type: $type");
    }

    private function validateIsArray(&$value, $type) {
        if (!is_array($value)) {
            throw $this->couldNotConvertException($value, $type);
        }
    }

    private function validateIsObject(&$value, $type) {
        if (!is_object($value)) {
            throw $this->couldNotConvertException($value, $type);
        }
    }

    private function validateIsBoolean(&$value) {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        throw $this->couldNotConvertException($value, 'boolean');
    }

    private function validateIsInt(&$value) {
        if (is_int($value)) {
            return $value;
        }
        if (ctype_digit(strval($value))) {
            return intval($value);
        }
        throw $this->couldNotConvertException($value, 'ineger');
    }

    private function validateIsDouble(&$value) {
        if (is_double($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return doubleval($value);
        }
        throw $this->couldNotConvertException($value, 'double');
    }

    private function validateIsString(&$value) {
        if (is_string($value)) {
            return $value;
        }
        if (is_scalar($value)) {
            return strval($value);
        }
        throw $this->couldNotConvertException($value, 'string');
    }

    private function couldNotConvertException(&$value, $type) {
        $valueType = gettype($value);
        if (is_scalar($value)) {
            throw new JSONParseException("Could convert value '$value' of type $valueType to $type");
        } else {
            throw new JSONParseException("Could not convert value of type $valueType to $type");
        }
    }

    /**************/
    /* inspection */
    /**************/

    private function inspectClass(ReflectionClass $class) {
        $className = $class->getName();
        if (array_key_exists($className, $this->classes)) {
            // already inspecting the class, prevent infinite recursion
            return;
        }

        $parentClass = $class->getParentClass();
        if (is_a($parentClass, '\ReflectionClass')) {
            $this->inspectClass($parentClass);
            $classDescriptor = ClassDescriptor::copy($this->classes[$parentClass->getName()], $class);
        } else {
            $classDescriptor = new ClassDescriptor($class);
        }
        $this->classes[$className] = $classDescriptor;

        $classAnnotations = $this->parseAnnotations($class->getDocComment());
        $accessorType = $this->getAccessorType($classAnnotations);

        $this->inspectProperties($class, $classDescriptor, $accessorType);
        $this->inspectMethods($class, $classDescriptor, $accessorType);
        $this->orderProperties($classDescriptor, $classAnnotations);
    }

    private function inspectProperties(ReflectionClass $class, ClassDescriptor $classDescriptor, $accessorType) {
        foreach ($class->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() === $classDescriptor->className()) {
                $propertyAnnotations = $this->parseAnnotations($property->getDocComment());
                if ($this->includeProperty($property, $accessorType, $propertyAnnotations)) {
                    $name = $this->getJSONPropertyName($property->getName(), $propertyAnnotations);
                    // overwrite what was already specified
                    $classDescriptor->addProperty($name, $this->getPropertyType($property, $propertyAnnotations))
                        ->withGetter(array_key_exists('JSONWriteOnly', $propertyAnnotations) ? null : $this->getPropertyGetter($property))
                        ->withSetter(array_key_exists('JSONReadOnly', $propertyAnnotations) ? null : $this->getPropertySetter($property))
                        ->withSerializer($this->getSerializer($property, $propertyAnnotations))
                        ->withDeserializer($this->getDeserializer($property, $propertyAnnotations));
                }
            }
        }
    }

    private function getPropertyGetter(ReflectionProperty $property) {
        $property->setAccessible(true);
        return array($property, 'getValue');
    }

    private function getPropertySetter(ReflectionProperty $property) {
        $property->setAccessible(true);
        return array($property, 'setValue');
    }

    private function inspectMethods(ReflectionClass $class, ClassDescriptor $classDescriptor, $accessorType) {
        $anyGetter = null;
        $anySetter = null;

        foreach ($class->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $classDescriptor->className()) {
                $methodAnnotations = $this->parseAnnotations($method->getDocComment());
                if (!$method->isStatic()) {
                    if ($this->includeMethod($method, $accessorType, $methodAnnotations)) {
                        $this->updatePropertyIfNeeded($classDescriptor, $method, $methodAnnotations);
                    }
                    if (array_key_exists('JSONAnyGetter', $methodAnnotations)) {
                        $this->validateAnyGetterMethod($method, $anyGetter);
                        $anyGetter = $method;
                    }
                    if (array_key_exists('JSONAnySetter', $methodAnnotations)) {
                        $this->validateAnySetterMethod($method, $anySetter);
                        $anySetter = $method;
                    }
                }
            }
        }
        if (!is_null($anyGetter)) {
            $classDescriptor->withAnyGetter($this->getMethodCallable($anyGetter));
        }
        if (!is_null($anySetter)) {
            $classDescriptor->withAnySetter($this->getMethodCallable($anySetter));
        }
    }

    private function updatePropertyIfNeeded(ClassDescriptor $classDescriptor, ReflectionMethod $method, $methodAnnotations) {
        $methodName = $method->getName();
        if (preg_match('/^(is|get|set)[_A-Z].*/', $methodName, $matches)) {
            if ($matches[1] === 'is' && $method->getNumberOfParameters() === 0) {
                $name = $this->extractPropertyNameFromMethod($methodName, 2);
                $name = $this->getJSONPropertyName($name, $methodAnnotations);
                $type = $this->getReturnType($method, $methodAnnotations);
                $this->updateProperty($classDescriptor, $method, $name, $type, $methodAnnotations, $method, null);
            } else if ($matches[1] === 'get' && $method->getNumberOfParameters() === 0) {
                $name = $this->extractPropertyNameFromMethod($methodName, 3);
                $name = $this->getJSONPropertyName($name, $methodAnnotations);
                $type = $this->getReturnType($method, $methodAnnotations);
                $this->updateProperty($classDescriptor, $method, $name, $type, $methodAnnotations, $method, null);
            } else if ($matches[1] === 'set' && $method->getNumberOfParameters() === 1) {
                $name = $this->extractPropertyNameFromMethod($methodName, 3);
                $name = $this->getJSONPropertyName($name, $methodAnnotations);
                $type = $this->getParameterType($method, $methodAnnotations);
                $this->updateProperty($classDescriptor, $method, $name, $type, $methodAnnotations, null, $setter);
            }
        }
    }

    private function updateProperty(ClassDescriptor $classDescriptor, ReflectionMethod $method, $name, $type, $methodAnnotations, ReflectionMethod $getter = null, ReflectionMethod $setter = null) {
        $propertyDescriptor = $classDescriptor->ensureProperty($name, $type);
        if (!is_null($getter)) {
            $propertyDescriptor->withGetter($this->getMethodCallable($getter));
            $propertyDescriptor->withSerializer($this->getSerializer($method, $methodAnnotations));
        }
        if (!is_null($setter)) {
            $propertyDescriptor->withSetter($this->getMethodCallable($setter));
            $propertyDescriptor->withDeserializer($this->getDeserializer($method, $methodAnnotations));
        }
    }

    private function validateAnyGetterMethod(ReflectionMethod $method, ReflectionMethod $existingAnyGetter = null) {
        if ($method->getNumberOfParameters() !== 0) {
            $methodName = $method->getName();
            $className = $method->getDeclaringClass()->getName();
            throw new JSONMappingException("@JSONAnyGetter method '$methodName' of class $className should not have any parameters");
        }
        if (!is_null($existingAnyGetter)) {
            throw new JSONMappingException("Only one method annotated with @JSONAnyGetter allowed for class $className");
        }
    }

    private function validateAnySetterMethod(ReflectionMethod $method, ReflectionMethod $existingAnySetter = null) {
        if ($method->getNumberOfParameters() !== 2) {
            $methodName = $method->getName();
            $className = $method->getDeclaringClass()->getName();
            throw new JSONMappingException("@JSONAnySetter method '$methodName' of class $className should have two parameters");
        }
        if (!is_null($existingAnySetter)) {
            throw new JSONMappingException("Only one method annotated with @JSONAnyGetter allowed for class $className");
        }
    }

    private function getMethodCallable(ReflectionMethod $method) {
        $method->setAccessible(true);
        return array($method, 'invoke');
    }

    private function orderProperties(ClassDescriptor $classDescriptor, $classAnnotations) {
        if (array_key_exists('JSONPropertyOrder', $classAnnotations)) {
            if ($classAnnotations['JSONPropertyOrder'][0] === 'ALPHABETICAL') {
                $classDescriptor->orderProperties();
            } else {
                $propertyNames = preg_split('/\s*,\s/', $classAnnotations['JSONPropertyOrder'][0]);
                $classDescriptor->orderProperties($propertyNames);
            }
        }
    }

    /******************/
    /* helper methods */
    /******************/

    // generic

    private function parseAnnotations($docComment) {
        $annotations = array();

        $pattern = '/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m';
        if (preg_match_all($pattern, $docComment, $matches)) {
            $matchCount = count($matches[0]);
            for ($i = 0; $i < $matchCount; $i++) {
                $annotations[$matches['name'][$i]][] = $matches['value'][$i];
            }
        }
        return $annotations;
    }

    // reflection

    private function createInstance(ReflectionClass $class) {
        $constructor = $class->getConstructor();
        if (is_null($constructor) || ($constructor->getNumberOfRequiredParameters() === 0 && $constructor->isPublic())) {
            return $class->newInstanceArgs();
        }
        if (method_exists('\ReflectionClass', 'newInstanceWithoutConstructor')) {
            $instance = $class->newInstanceWithoutConstructor();
            if ($constructor->getNumberOfRequiredParameters() === 0) {
                $constructor->setAccessible(true);
                $constructor->invoke($instance);
            }
            return $instance;
        }
        $className = $class->getName();
        throw new JSONMappingException("Class $className does not have a non-argument constructor");
    }

    // classes

    private function getAccessorType($classAnnotations) {
        if (array_key_exists('JSONAccessorType', $classAnnotations)) {
            $accessorType = $classAnnotations['JSONAccessorType'][0];
            $validAccessorTypes = array(
                self::$ACCESSOR_TYPE_METHOD,
                self::$ACCESSOR_TYPE_PROPERTY,
                self::$ACCESSOR_TYPE_PUBLIC_MEMBER,
                self::$ACCESSOR_TYPE_NONE,
            );
            if (in_array($accessorType, $validAccessorTypes)) {
                return $accessorType;
            }
            throw new JSONMappingException("Invalid value for @JSONAccessorType: $accessorType");
        }
        return self::$ACCESSOR_TYPE_PUBLIC_MEMBER;
    }

    // members

    private function includeMember($member, $accessorType, $memberAnnotations, $expectedMemberAccessorType) {
        if (array_key_exists('JSONIgnore', $memberAnnotations)) {
            return false;
        }
        if (array_key_exists('JSONProperty', $memberAnnotations)) {
            return true;
        }
        return ($accessorType === self::$ACCESSOR_TYPE_PUBLIC_MEMBER && $member->isPublic()) || $accessorType === $expectedMemberAccessorType;
    }

    private function getJSONPropertyName($candidate, $memberAnnotations) {
        if (array_key_exists('JSONProperty', $memberAnnotations)) {
            $result = $memberAnnotations['JSONProperty'][0];
            if ($result !== '') {
                return $result;
            }
        }
        return $candidate;
    }

    private function getSerializer($member, $memberAnnotations) {
        return $this->getSerializerOrDeserializer($member, $memberAnnotations, 'JSONSerializer', 'Robtimus\\JSON\\Mapper\\JSONSerializer');
    }

    private function getDeserializer($member, $memberAnnotations) {
        return $this->getSerializerOrDeserializer($member, $memberAnnotations, 'JSONDeserializer', 'Robtimus\\JSON\\Mapper\\JSONDeserializer');
    }

    private function getSerializerOrDeserializer($member, $memberAnnotations, $annotation, $interface) {
        if (array_key_exists($annotation, $memberAnnotations)) {
            $type = $memberAnnotations[$annotation][0];
            if ($type === '') {
                $memberType = is_a('\ReflectionMethod', $member) ? 'method' : 'property';
                $memberName = $member->getName();
                $className = $member->getDeclaringClass()->getName();
                throw new JSONMappingException("Missing @$annotation type for $memberType '$memberName' of class $className");
            }
            $type = explode(' ', $type)[0];
            $type = explode('|', $type)[0];
            $type = TypeHelper::resolveType($type, $member->getDeclaringClass());
            if (TypeHelper::isScalarType($type) || substr($type, -2) === '[]') {
                $memberType = is_a('\ReflectionMethod', $member) ? 'method' : 'property';
                $memberName = $member->getName();
                $className = $member->getDeclaringClass()->getName();
                throw new JSONMappingException("Invalid @$annotation type for $memberType '$memberName' of class $className");
            }

            $class = new ReflectionClass($type);
            if (!$class->implementsInterface($interface)) {
                $memberType = is_a('\ReflectionMethod', $member) ? 'method' : 'property';
                $memberName = $member->getName();
                $className = $member->getDeclaringClass()->getName();
                throw new JSONMappingException("Invalid @$annotation type for $memberType '$memberName' of class $className");
            }

            if (!array_key_exists($type, $this->instanceCache)) {
                $this->instanceCache[$type] = $this->createInstance($class);
            }
            return $this->instanceCache[$type];
        }
        return null;
    }

    // properties

    private function includeProperty($property, $accessorType, $propertyAnnotations) {
        return $this->includeMember($property, $accessorType, $propertyAnnotations, self::$ACCESSOR_TYPE_PROPERTY);
    }

    private function getPropertyType($property, $propertyAnnotations) {
        $type = array_key_exists('var', $propertyAnnotations) ? $propertyAnnotations['var'][0] : '';
        if ($type === '') {
            $propertyName = $property->getName();
            $className = $property->getDeclaringClass()->getName();
            throw new JSONMappingException("Missing @var type for property '$propertyName' of class $className");
        }
        return TypeHelper::resolveType($type, $property->getDeclaringClass());
    }

    // methods

    private function includeMethod($method, $accessorType, $methodAnnotations) {
        return $this->includeMember($method, $accessorType, $methodAnnotations, self::$ACCESSOR_TYPE_METHOD);
    }

    private function extractPropertyNameFromMethod($methodName, $prefixLength) {
        $result = substr($methodName, $prefixLength);
        while (substr($result, 0, 1) === '_') {
            $result = substr($result, 1);
        }
        if (strlen($result) > 1 && strtoupper($result) === $result) {
            // fully uppercase, turn into fully lowercase
            return strtolower($result);
        }
        return $result === '' ? '' : strtolower(substr($result, 0, 1)) . substr($result, 1);
    }

    private function getReturnType(ReflectionMethod $method, $methodAnnotations) {
        $type = array_key_exists('return', $methodAnnotations) ? $methodAnnotations['return'][0] : '';
        if ($type === '') {
            $methodName = $method->getName();
            $className = $method->getDeclaringClass()->getName();
            throw new JSONMappingException("Missing @return type for method '$methodName' of class $className");
        }
        $type = explode(' ', $type)[0];
        $type = explode('|', $type)[0];
        return TypeHelper::resolveType($type, $method->getDeclaringClass());
    }

    private function getParameterType(ReflectionMethod $method, $methodAnnotations) {
        $parameterTypes = $this->parseParameters($methodAnnotations);
        $parameterName = $method->getParameters()[0]->getName();
        $type = array_key_exists($parameterName, $parameterTypes) ? $parameterTypes[$parameterName] : '';
        if ($type === '') {
            $methodName = $method->getName();
            $className = $method->getDeclaringClass()->getName();
            throw new JSONMappingException("Missing @param type for parameter '$parameterName' of method '$methodName' of class $className");
        }
        $type = explode(' ', $type)[0];
        $type = explode('|', $type)[0];
        return TypeHelper::resolveType($type, $method->getDeclaringClass());
    }

    private function parseParameters($methodAnnotations) {
        $parameters = array();

        $pattern = '/^(?P<type>[A-Za-z0-9\\\\_-]+)[ \t]+\\$(?P<name>\S+?)[ \t]*.*\r?$/';
        if (array_key_exists('param', $methodAnnotations)) {
            foreach ($methodAnnotations['param'] as $value) {
                if (preg_match($pattern, $value, $matches)) {
                    $parameters[$matches['name']] = $matches['type'];
                }
            }
        }
        return $parameters;
    }
}
