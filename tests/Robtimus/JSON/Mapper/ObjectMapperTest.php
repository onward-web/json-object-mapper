<?php
namespace Robtimus\JSON\Mapper;

use stdClass;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Doctrine\Common\Annotations\AnnotationException;
use PHPUnit\Framework\TestCase;
use Robtimus\JSON\Mapper\Annotations\JSONAccessorType;
use Robtimus\JSON\Mapper\Annotations\JSONAnyGetter;
use Robtimus\JSON\Mapper\Annotations\JSONAnySetter;
use Robtimus\JSON\Mapper\Annotations\JSONDeserialize;
use Robtimus\JSON\Mapper\Annotations\JSONIgnore;
use Robtimus\JSON\Mapper\Annotations\JSONProperty;
use Robtimus\JSON\Mapper\Annotations\JSONPropertyOrder;
use Robtimus\JSON\Mapper\Annotations\JSONReadOnly;
use Robtimus\JSON\Mapper\Annotations\JSONSerialize;
use Robtimus\JSON\Mapper\Annotations\JSONWriteOnly;
use Robtimus\JSON\Mapper\Descriptors\ClassDescriptor;

class ObjectMapperTest extends TestCase {

    // TODO: add more tests

    // orderProperties

    public function testOrderPropertiesNoAnnotation() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithNoAnnotations'));

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('z', 'y', 'x'), array_keys($properties));
    }

    public function testOrderPropertiesEmptyAnnotation() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithEmptyPropertyOrder'));

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('z', 'y', 'x'), array_keys($properties));
    }

    public function testOrderPropertiesAlphabetical() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithAlphabeticalPropertyOrder'));

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('x', 'y', 'z'), array_keys($properties));
    }

    public function testOrderPropertiesAlphabeticalFalse() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithAlphabeticalFalsePropertyOrder'));

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('z', 'y', 'x'), array_keys($properties));
    }

    public function testOrderPropertiesSpecified() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        $method->invoke($mapper, $classDescriptor, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithSpecificPropertyOrder'));

        $properties = $classDescriptor->properties();

        $this->assertEquals(array('y', 'x', 'z'), array_keys($properties));
    }

    public function testOrderPropertiesEmptySpecified() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $classDescriptor, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithEmptySpecifiedPropertyOrder'));
        } catch (JSONMappingException $e) {
            $this->assertEquals('Properties must include all properties for class stdClass; expected: z,y,x, given: ', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testOrderPropertiesWithBothAlphabeticalAndSpecificOrder() {
        $classDescriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $classDescriptor->addProperty('z', 'int');
        $classDescriptor->addProperty('y', 'int');
        $classDescriptor->addProperty('x', 'int');

        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'orderProperties');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $classDescriptor, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithAlphabeticalAndSpecificPropertyOrder'));
        } catch (JSONMappingException $e) {
            $this->assertEquals('@JSONPropertyOrder on class stdClass should not define both alphabetical and properties', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // createInstance

    public function testCreateInstanceWithNoConstructor() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\JSON\Mapper\ClassWithNoConstructor');

        $instance = $method->invoke($mapper, $class);

        $this->assertInstanceOf($class->getName(), $instance);
    }

    public function testCreateInstanceWithPublicNonArgConstructor() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\JSON\Mapper\ClassWithPublicNonArgConstructor');

        $instance = $method->invoke($mapper, $class);

        $this->assertInstanceOf($class->getName(), $instance);
    }

    public function testCreateInstanceWithPublicConstructorWithOptionalArgument() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\JSON\Mapper\ClassWithPublicConstructorWithOptionalArgument');

        $instance = $method->invoke($mapper, $class);

        $this->assertInstanceOf($class->getName(), $instance);
    }

    public function testCreateInstanceWithPublicConstructorWithRequiredArgument() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\JSON\Mapper\ClassWithPublicConstructorWithRequiredArgument');

        $instance = $method->invoke($mapper, $class);

        $this->assertInstanceOf($class->getName(), $instance);
    }

    public function testCreateInstanceWithPrivateNonArgConstructor() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\JSON\Mapper\ClassWithPrivateNonArgConstructor');

        if (method_exists('\ReflectionClass', 'newInstanceWithoutConstructor')) {
            $instance = $method->invoke($mapper, $class);

            $this->assertInstanceOf($class->getName(), $instance);
        } else {
            try {
                $method->invoke($mapper, $class);
            } catch (JSONMappingException $e) {
                $this->assertEquals("Class Robtimus\JSON\Mapper\ClassWithPrivateNonArgConstructor does not have a non-argument constructor", $e->getMessage());
                return;
            }
            $this->fail('Expected a JSONMappingException');
        }
    }

    public function testCreateInstanceWithPrivateConstructorWithOptionalArgument() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\JSON\Mapper\ClassWithPrivateConstructorWithOptionalArgument');

        if (method_exists('\ReflectionClass', 'newInstanceWithoutConstructor')) {
            $instance = $method->invoke($mapper, $class);

            $this->assertInstanceOf($class->getName(), $instance);
        } else {
            try {
                $method->invoke($mapper, $class);
            } catch (JSONMappingException $e) {
                $this->assertEquals("Class Robtimus\JSON\Mapper\ClassWithPrivateConstructorWithOptionalArgument does not have a non-argument constructor", $e->getMessage());
                return;
            }
            $this->fail('Expected a JSONMappingException');
        }
    }

    public function testCreateInstanceWithPrivateConstructorWithRequiredArgument() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'createInstance');
        $method->setAccessible(true);

        $class = new ReflectionClass('Robtimus\JSON\Mapper\ClassWithPrivateConstructorWithRequiredArgument');

        if (method_exists('\ReflectionClass', 'newInstanceWithoutConstructor')) {
            $instance = $method->invoke($mapper, $class);

            $this->assertInstanceOf($class->getName(), $instance);
        } else {
            try {
                $method->invoke($mapper, $class);
            } catch (JSONMappingException $e) {
                $this->assertEquals("Class Robtimus\JSON\Mapper\ClassWithPrivateConstructorWithRequiredArgument does not have a non-argument constructor", $e->getMessage());
                return;
            }
            $this->fail('Expected a JSONMappingException');
        }
    }

    // getAccessorType

    public function testGetAccessorTypeNotPresent() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        $accessorType = $method->invoke($mapper, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithNoAnnotations'));

        $this->assertEquals('PUBLIC_MEMBER', $accessorType);
    }

    public function testGetAccessorTypeWithValidAccessorType() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        $accessorType = $method->invoke($mapper, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithValidAccessorType'));

        $this->assertEquals('PROPERTY', $accessorType);
    }

    public function testGetAccessorTypeWithInvalidAccessorType() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithInvalidAccessorType'));
        } catch (AnnotationException $e) {
            $this->assertRegExp('/FIELD/', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetAccessorTypeWithAccessorTypeWithoutValue() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getAccessorType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, new ReflectionClass('Robtimus\JSON\Mapper\ClassWithAccessorTypeWithoutValue'));
        } catch (AnnotationException $e) {
            $this->assertRegExp('/value/', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // getJSONPropertyName

    public function testGetJSONPropertyNamePropertyNoAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'publicProperty');

        $name = $method->invoke($mapper, $testProperty, 'name');

        $this->assertEquals('name', $name);
    }

    public function testGetJSONPropertyNamePropertyEmptyAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'includedProperty');

        $name = $method->invoke($mapper, $testProperty, 'name');

        $this->assertEquals('name', $name);
    }

    public function testGetJSONPropertyNamePropertyNonEmptyAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'namedProperty');

        $name = $method->invoke($mapper, $testProperty, 'name');

        $this->assertEquals('otherName', $name);
    }

    public function testGetJSONPropertyNameMethodNoAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getPublicProperty');

        $name = $method->invoke($mapper, $testMethod, 'name');

        $this->assertEquals('name', $name);
    }

    public function testGetJSONPropertyNameMethodEmptyAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getIncludedProperty');

        $name = $method->invoke($mapper, $testMethod, 'name');

        $this->assertEquals('name', $name);
    }

    public function testGetJSONPropertyNameMethodNonEmptyAnnotation() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'getJSONPropertyName');
        $method->setAccessible(true);

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNamedProperty');

        $name = $method->invoke($mapper, $testMethod, 'name');

        $this->assertEquals('otherName', $name);
    }

    // getSerializer

    public function testGetSerializerPropertyNotPresent() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'notAnnotatedProperty');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        $serializer = $method->invoke($mapper, $member);

        $this->assertNull($serializer);
    }

    public function testGetSerializerPropertyMissingType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithMissingType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (AnnotationException $e) {
            $this->assertRegExp('/using/', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerPropertyEmptyType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithEmptyType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Empty uses in @Robtimus\JSON\Mapper\Annotations\JSONSerialize for property 'propertyWithEmptyType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerPropertyScalarType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithScalarType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONSerialize.uses for property 'propertyWithScalarType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerPropertyArrayType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithArrayType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONSerialize.uses for property 'propertyWithArrayType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerPropertyIncompatibleType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithIncompatibleType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONSerialize.uses for property 'propertyWithIncompatibleType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerProperty() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'validProperty');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        $serializer = $method->invoke($mapper, $member);

        $this->assertInstanceOf('Robtimus\JSON\Mapper\JSONSerializer', $serializer);

        $secondSerializer = $method->invoke($mapper, $member);

        $this->assertSame($serializer, $secondSerializer);
    }

    public function testGetSerializerMethodNotPresent() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'notAnnotatedMethod');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        $serializer = $method->invoke($mapper, $member);

        $this->assertNull($serializer);
    }

    public function testGetSerializerMethodMissingType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithMissingType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (AnnotationException $e) {
            $this->assertRegExp('/using/', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerMethodEmptyType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithEmptyType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Empty uses in @Robtimus\JSON\Mapper\Annotations\JSONSerialize for method 'methodWithEmptyType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerMethodScalarType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithScalarType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONSerialize.uses for method 'methodWithScalarType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerMethodArrayType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithArrayType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONSerialize.uses for method 'methodWithArrayType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerMethodIncompatibleType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithIncompatibleType');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONSerialize.uses for method 'methodWithIncompatibleType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetSerializerMethod() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'validMethod');

        $method = new ReflectionMethod($mapper, 'getSerializer');
        $method->setAccessible(true);

        $serializer = $method->invoke($mapper, $member);

        $this->assertInstanceOf('Robtimus\JSON\Mapper\JSONSerializer', $serializer);

        $secondSerializer = $method->invoke($mapper, $member);

        $this->assertSame($serializer, $secondSerializer);
    }

    // getDeserializer

    public function testGetDeserializerPropertyNotPresent() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'notAnnotatedProperty');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        $deserializer = $method->invoke($mapper, $member);

        $this->assertNull($deserializer);
    }

    public function testGetDeserializerPropertyMissingType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithMissingType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (AnnotationException $e) {
            $this->assertRegExp('/using/', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerPropertyEmptyType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithEmptyType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Empty uses in @Robtimus\JSON\Mapper\Annotations\JSONDeserialize for property 'propertyWithEmptyType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerPropertyScalarType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithScalarType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONDeserialize.uses for property 'propertyWithScalarType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerPropertyArrayType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithArrayType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONDeserialize.uses for property 'propertyWithArrayType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerPropertyIncompatibleType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'propertyWithIncompatibleType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONDeserialize.uses for property 'propertyWithIncompatibleType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerProperty() {
        $mapper = new ObjectMapper();

        $member = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'validProperty');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        $deserializer = $method->invoke($mapper, $member);

        $this->assertInstanceOf('Robtimus\JSON\Mapper\JSONDeserializer', $deserializer);

        $secondDeserializer = $method->invoke($mapper, $member);

        $this->assertSame($deserializer, $secondDeserializer);
    }

    public function testGetDeserializerMethodNotPresent() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'notAnnotatedMethod');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        $deserializer = $method->invoke($mapper, $member);

        $this->assertNull($deserializer);
    }

    public function testGetDeserializerMethodMissingType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithMissingType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (AnnotationException $e) {
            $this->assertRegExp('/using/', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerMethodEmptyType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithEmptyType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Empty uses in @Robtimus\JSON\Mapper\Annotations\JSONDeserialize for method 'methodWithEmptyType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerMethodScalarType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithScalarType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONDeserialize.uses for method 'methodWithScalarType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerMethodArrayType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithArrayType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONDeserialize.uses for method 'methodWithArrayType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerMethodIncompatibleType() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'methodWithIncompatibleType');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $member);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Invalid type in @Robtimus\JSON\Mapper\Annotations\JSONDeserialize.uses for method 'methodWithIncompatibleType' of class Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetDeserializerMethod() {
        $mapper = new ObjectMapper();

        $member = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithSerializersAndDeserializers', 'validMethod');

        $method = new ReflectionMethod($mapper, 'getDeserializer');
        $method->setAccessible(true);

        $deserializer = $method->invoke($mapper, $member);

        $this->assertInstanceOf('Robtimus\JSON\Mapper\JSONDeserializer', $deserializer);

        $secondDeserializer = $method->invoke($mapper, $member);

        $this->assertSame($deserializer, $secondDeserializer);
    }

    // includeProperty

    public function testIncludePropertyIgnored() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'ignoredProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, JSONAccessorType::ACCESSOR_TYPE_PROPERTY);

        $this->assertEquals(false, $include);
    }

    public function testIncludePropertyIncluded() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'includedProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, JSONAccessorType::ACCESSOR_TYPE_NONE);

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyIncludedWithName() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'namedProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, JSONAccessorType::ACCESSOR_TYPE_NONE);

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyAccessorTypePublicMemberWithPublicProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'publicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, JSONAccessorType::ACCESSOR_TYPE_PUBLIC_MEMBER);

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyAccessorTypePublicMemberWithNonPublicProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, JSONAccessorType::ACCESSOR_TYPE_PUBLIC_MEMBER);

        $this->assertEquals(false, $include);
    }

    public function testIncludePropertyAccessorTypeProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, JSONAccessorType::ACCESSOR_TYPE_PROPERTY);

        $this->assertEquals(true, $include);
    }

    public function testIncludePropertyAccessorTypeNonProperty() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'nonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeProperty');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testProperty, JSONAccessorType::ACCESSOR_TYPE_METHOD);

        $this->assertEquals(false, $include);
    }

    // getPropertyType

    public function testGetPropertyTypeNoAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithAnnotations', 'notAnnotated');

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testProperty, $testProperty->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @var type for property 'notAnnotated' of class Robtimus\JSON\Mapper\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetPropertyTypeWithAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithAnnotations', 'properlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testProperty, $testProperty->getDocComment());

        $this->assertEquals('string', $type);
    }

    public function testGetPropertyTypeWithMinimalAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithAnnotations', 'minimallyAnnotated');

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testProperty, $testProperty->getDocComment());

        $this->assertEquals('string', $type);
    }

    public function testGetPropertyTypeWithIncorrectAnnotations() {
        $mapper = new ObjectMapper();

        $testProperty = new ReflectionProperty('Robtimus\JSON\Mapper\ClassWithAnnotations', 'incorrectlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getPropertyType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testProperty, $testProperty->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @var type for property 'incorrectlyAnnotated' of class Robtimus\JSON\Mapper\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // includeMethod

    public function testIncludeMethodIgnored() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getIgnoredProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, JSONAccessorType::ACCESSOR_TYPE_METHOD);

        $this->assertEquals(false, $include);
    }

    public function testIncludeMethodIncluded() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getIncludedProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, JSONAccessorType::ACCESSOR_TYPE_NONE);

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodIncludedWithName() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNamedProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, JSONAccessorType::ACCESSOR_TYPE_NONE);

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodAccessorTypePublicMemberWithPublicMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, JSONAccessorType::ACCESSOR_TYPE_PUBLIC_MEMBER);

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodAccessorTypePublicMemberWithNonPublicMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, JSONAccessorType::ACCESSOR_TYPE_PUBLIC_MEMBER);

        $this->assertEquals(false, $include);
    }

    public function testIncludeMethodAccessorTypeMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, JSONAccessorType::ACCESSOR_TYPE_METHOD);

        $this->assertEquals(true, $include);
    }

    public function testIncludeMethodAccessorTypeNonMethod() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithPublicAndNonPublicPropertiesAndMethods', 'getNonPublicProperty');

        $method = new ReflectionMethod($mapper, 'includeMethod');
        $method->setAccessible(true);

        $include = $method->invoke($mapper, $testMethod, JSONAccessorType::ACCESSOR_TYPE_PROPERTY);

        $this->assertEquals(false, $include);
    }

    // extractPropertyNameFromMethod

    public function testExtractPropertyNameFromMethodOnlyPrefix() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'extractPropertyNameFromMethod');
        $method->setAccessible(true);

        $propertyName = $method->invoke($mapper, 'get', 3);

        $this->assertEquals('', $propertyName);
    }

    public function testExtractPropertyNameFromMethodSnakeCase() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'extractPropertyNameFromMethod');
        $method->setAccessible(true);

        $propertyName = $method->invoke($mapper, 'get_some_value', 3);

        $this->assertEquals('some_value', $propertyName);
    }

    public function testExtractPropertyNameFromMethodCamelCase() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'extractPropertyNameFromMethod');
        $method->setAccessible(true);

        $propertyName = $method->invoke($mapper, 'getSomeValue', 3);

        $this->assertEquals('someValue', $propertyName);
    }

    public function testExtractPropertyNameFromMethodUpperCase() {
        $mapper = new ObjectMapper();

        $method = new ReflectionMethod($mapper, 'extractPropertyNameFromMethod');
        $method->setAccessible(true);

        $propertyName = $method->invoke($mapper, 'getSOMEVALUE', 3);

        $this->assertEquals('somevalue', $propertyName);
    }

    // getReturnType

    public function testGetReturnTypeNoAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithAnnotations', 'getNotAnnotated');

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $testMethod->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @return type for method 'getNotAnnotated' of class Robtimus\JSON\Mapper\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetReturnTypeWithAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithAnnotations', 'getProperlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $testMethod->getDocComment());

        $this->assertEquals('string', $type);
    }

    public function testGetReturnTypeWithMinimalAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithAnnotations', 'getMinimallyAnnotatedReturn');

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $testMethod->getDocComment());

        $this->assertEquals('string', $type);
    }

    public function testGetReturnTypeWithIncorrectAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithAnnotations', 'getIncorrectlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getReturnType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $testMethod->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @return type for method 'getIncorrectlyAnnotated' of class Robtimus\JSON\Mapper\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // getParameterType

    public function testGetParameterTypeNoAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithAnnotations', 'getNotAnnotated');

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $testMethod->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @param type for parameter 'x' of method 'getNotAnnotated' of class Robtimus\JSON\Mapper\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testGetParameterTypeWithAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithAnnotations', 'getProperlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $testMethod->getDocComment());

        $this->assertEquals('int', $type);
    }

    public function testGetParameterTypeWithMinimalAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithAnnotations', 'getMinimallyAnnotatedParam');

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        $type = $method->invoke($mapper, $testMethod, $testMethod->getDocComment());

        $this->assertEquals('int', $type);
    }

    public function testGetParameterTypeWithIncorrectAnnotations() {
        $mapper = new ObjectMapper();

        $testMethod = new ReflectionMethod('Robtimus\JSON\Mapper\ClassWithAnnotations', 'getIncorrectlyAnnotated');

        $method = new ReflectionMethod($mapper, 'getParameterType');
        $method->setAccessible(true);

        try {
            $method->invoke($mapper, $testMethod, $testMethod->getDocComment());
        } catch (JSONMappingException $e) {
            $this->assertEquals("Missing @param type for parameter 'x' of method 'getIncorrectlyAnnotated' of class Robtimus\JSON\Mapper\ClassWithAnnotations", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }
}

// utility classes

// create instance

class ClassWithNoConstructor {
}

class ClassWithPublicNonArgConstructor {
    public function __construct() {}
}

class ClassWithPublicConstructorWithOptionalArgument {
    public function __construct($x = 0) {}
}

class ClassWithPublicConstructorWithRequiredArgument {
    public function __construct($x) {}
}

class ClassWithPrivateNonArgConstructor {
    private function __construct() {}
}

class ClassWithPrivateConstructorWithOptionalArgument {
    private function __construct($x = 0) {}
}

class ClassWithPrivateConstructorWithRequiredArgument {
    private function __construct($x) {}
}

// annotation testing

class ClassWithNoAnnotations {
}

// property order

/**
 * @JSONPropertyOrder
 */
class ClassWithEmptyPropertyOrder {
}

/**
 * @JSONPropertyOrder(alphabetical = true)
 */
class ClassWithAlphabeticalPropertyOrder {
}

/**
 * @JSONPropertyOrder(alphabetical = false)
 */
class ClassWithAlphabeticalFalsePropertyOrder {
}

/**
 * @JSONPropertyOrder(alphabetical = false, properties = {"y", "x", "z"})
 */
class ClassWithSpecificPropertyOrder {
}

/**
 * @JSONPropertyOrder(properties = {})
 */
class ClassWithEmptySpecifiedPropertyOrder {
}

/**
 * @JSONPropertyOrder(alphabetical = true, properties = {})
 */
class ClassWithAlphabeticalAndSpecificPropertyOrder {
}

// accessor type

/**
 * @JSONAccessorType("PROPERTY")
 */
class ClassWithValidAccessorType {
}

/**
 * @JSONAccessorType("FIELD")
 */
class ClassWithInvalidAccessorType {
}

/**
 * @JSONAccessorType()
 */
class ClassWithAccessorTypeWithoutValue {
}

// other

class ClassWithAnnotations {

    protected $notAnnotated;

    /**
     * @var string
     */
    protected $properlyAnnotated;

    /** @var string */
    protected $minimallyAnnotated;

    /**
     * @var
     */
    protected $incorrectlyAnnotated;

    protected function getNotAnnotated($x) {
    }

    /**
     * @param int $x
     * @return string
     */
    protected function getProperlyAnnotated($x) {
    }

    /** @param int $x */
    protected function getMinimallyAnnotatedParam($x) {
    }

    /** @return string */
    protected function getMinimallyAnnotatedReturn($x) {
    }

    /**
     * @param
     * @return
     */
    protected function getIncorrectlyAnnotated($x) {
    }
}

class ClassWithPublicAndNonPublicPropertiesAndMethods {

    public $publicProperty;

    /** @JSONIgnore */
    public $ignoredProperty;

    protected $nonPublicProperty;

    /** @JSONProperty */
    protected $includedProperty;

    /** @JSONProperty(name = "otherName") */
    protected $namedProperty;

    public function getPublicProperty() {
    }

    /** @JSONIgnore */
    public function getIgnoredProperty() {
    }

    protected function getNonPublicProperty() {
    }

    /** @JSONProperty */
    protected function getIncludedProperty() {
    }

    /** @JSONProperty(name = "otherName") */
    public function getNamedProperty() {
    }
}

class ClassWithSerializersAndDeserializers {

    public $notAnnotatedProperty;

    /**
     * @JSONSerialize()
     * @JSONDeserialize()
     */
    public $propertyWithMissingType;

    /**
     * @JSONSerialize(using = "")
     * @JSONDeserialize(using = "")
     */
    public $propertyWithEmptyType;

    /**
     * @JSONSerialize(using = "int")
     * @JSONDeserialize(using = "int")
     */
    public $propertyWithScalarType;

    /**
     * @JSONSerialize(using = "\stdClass[]")
     * @JSONDeserialize(using = "\stdClass[]")
     */
    public $propertyWithArrayType;

    /**
     * @JSONSerialize(using = "\stdClass")
     * @JSONDeserialize(using = "\stdClass")
     */
    public $propertyWithIncompatibleType;

    /**
     * @JSONSerialize(using = "DummySerializer")
     * @JSONDeserialize(using = "DummyDeserializer")
     */
    public $validProperty;

    public function notAnnotatedMethod() {
    }

    /**
     * @JSONSerialize()
     * @JSONDeserialize()
     */
    public function methodWithMissingType() {
    }

    /**
     * @JSONSerialize(using = "")
     * @JSONDeserialize(using = "")
     */
    public function methodWithEmptyType() {
    }

    /**
     * @JSONSerialize(using = "int")
     * @JSONDeserialize(using = "int")
     */
    public function methodWithScalarType() {
    }

    /**
     * @JSONSerialize(using = "\stdClass[]")
     * @JSONDeserialize(using = "\stdClass[]")
     */
    public function methodWithArrayType() {
    }

    /**
     * @JSONSerialize(using = "\stdClass")
     * @JSONDeserialize(using = "\stdClass")
     */
    public function methodWithIncompatibleType() {
    }

    /**
     * @JSONSerialize(using = "DummySerializer")
     * @JSONDeserialize(using = "DummyDeserializer")
     */
    public function validMethod() {
    }
}

class DummySerializer implements JSONSerializer {
    public function toJSON($value) {
        return $value;
    }
}

class DummyDeserializer implements JSONDeserializer {
    public function fromJSON($value, $type) {
        return $value;
    }
}
