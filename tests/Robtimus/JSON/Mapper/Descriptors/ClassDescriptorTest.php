<?php
namespace Robtimus\JSON\Mapper\Descriptors;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use Robtimus\JSON\Mapper\JSONMappingException;
use Robtimus\JSON\Mapper\Serializers\DateTimeJSONSerializer;

class ClassDescriptorTest extends TestCase {

    // addProperty

    public function testAddPropertyNew() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));

        $property = $descriptor->addProperty('x', 'int');

        $this->assertInstanceOf('\Robtimus\JSON\Mapper\Descriptors\PropertyDescriptor', $property);
        $this->assertEquals('int', $property->type());
        $this->assertNull($property->getter());
        $this->assertNull($property->setter());
        $this->assertNull($property->serializer());
        $this->assertNull($property->deserializer());
    }

    public function testAddPropertyExisting() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        
        $descriptor->addProperty('x', 'int')
            ->withSerializer(new DateTimeJSONSerializer());

        $property = $descriptor->addProperty('x', 'int');

        $this->assertInstanceOf('\Robtimus\JSON\Mapper\Descriptors\PropertyDescriptor', $property);
        $this->assertEquals('int', $property->type());
        $this->assertNull($property->getter());
        $this->assertNull($property->setter());
        $this->assertNull($property->serializer());
        $this->assertNull($property->deserializer());
    }

    public function testAddPropertyExistingTypeMismatch() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        
        $descriptor->addProperty('x', 'int');

        $property = $descriptor->addProperty('x', 'string');

        $this->assertInstanceOf('\Robtimus\JSON\Mapper\Descriptors\PropertyDescriptor', $property);
        $this->assertEquals('string', $property->type());
        $this->assertNull($property->getter());
        $this->assertNull($property->setter());
        $this->assertNull($property->serializer());
        $this->assertNull($property->deserializer());
    }

    // ensureProperty

    public function testEnsurePropertyNew() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));

        $property = $descriptor->ensureProperty('x', 'int');

        $this->assertInstanceOf('\Robtimus\JSON\Mapper\Descriptors\PropertyDescriptor', $property);
        $this->assertEquals('int', $property->type());
        $this->assertNull($property->getter());
        $this->assertNull($property->setter());
        $this->assertNull($property->serializer());
        $this->assertNull($property->deserializer());
    }

    public function testEnsurePropertyExisting() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        
        $descriptor->ensureProperty('x', 'int')
            ->withSerializer(new DateTimeJSONSerializer());

        $property = $descriptor->ensureProperty('x', 'int');

        $this->assertInstanceOf('\Robtimus\JSON\Mapper\Descriptors\PropertyDescriptor', $property);
        $this->assertEquals('int', $property->type());
        $this->assertNull($property->getter());
        $this->assertNull($property->setter());
        $this->assertInstanceOf('\Robtimus\JSON\Mapper\Serializers\DateTimeJSONSerializer', $property->serializer());
        $this->assertNull($property->deserializer());
    }

    public function testEnsurePropertyExistingTypeMismatch() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        
        $descriptor->ensureProperty('x', 'int');

        try {
            $descriptor->ensureProperty('x', 'string');
        } catch (JSONMappingException $e) {
            $this->assertEquals("Found different types for property 'x' of class stdClass", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    // orderProperties

    public function testOrderPropertiesAlphabetical() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $descriptor->addProperty('z', 'int');
        $descriptor->addProperty('y', 'int');
        $descriptor->addProperty('x', 'int');

        $descriptor->orderProperties();

        $properties = $descriptor->properties();

        $this->assertEquals(array('x', 'y', 'z'), array_keys($properties));
    }

    public function testOrderPropertiesSpecified() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $descriptor->addProperty('z', 'int');
        $descriptor->addProperty('y', 'int');
        $descriptor->addProperty('x', 'int');

        $propertyNames = array('y', 'x', 'z');

        $descriptor->orderProperties($propertyNames);

        $properties = $descriptor->properties();

        $this->assertEquals(array('y', 'x', 'z'), array_keys($properties));
    }

    public function testOrderPropertiesMissingProperties() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $descriptor->addProperty('x', 'int');
        $descriptor->addProperty('y', 'int');
        $descriptor->addProperty('z', 'int');

        $propertyNames = array('y', 'x');

        try {
            $descriptor->orderProperties($propertyNames);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Properties must include all properties for class stdClass; expected: x,y,z, given: y,x", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testOrderPropertiesExtraProperties() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $descriptor->addProperty('x', 'int');
        $descriptor->addProperty('y', 'int');
        $descriptor->addProperty('z', 'int');

        $propertyNames = array('y', 'a', 'x', 'z');

        try {
            $descriptor->orderProperties($propertyNames);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Properties must include all properties for class stdClass; expected: x,y,z, given: y,a,x,z", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testOrderPropertiesNonExistingProperties() {
        $descriptor = new ClassDescriptor(new ReflectionClass('stdClass'));
        $descriptor->addProperty('x', 'int');
        $descriptor->addProperty('y', 'int');
        $descriptor->addProperty('z', 'int');

        $propertyNames = array('x', 'y', 'a');

        try {
            $descriptor->orderProperties($propertyNames);
        } catch (JSONMappingException $e) {
            $this->assertEquals("Properties must include all properties for class stdClass; expected: x,y,z, given: x,y,a", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }
}
