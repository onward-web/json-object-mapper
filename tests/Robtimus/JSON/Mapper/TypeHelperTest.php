<?php
namespace Robtimus\JSON\Mapper;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework as F;

class TypeHelperTest extends F\TestCase {

    // resolveType

    public function testResolveTypeForArray() {
        $type = 'int[]';
        $this->assertEquals($type, TypeHelper::resolveType($type));
    }

    public function testResolveTypeForScalar() {
        $type = 'int';
        $this->assertEquals($type, TypeHelper::resolveType($type));
    }

    public function testResolveTypeForRelativeClassNoNamespace() {
        $type = 'stdClass';
        $class = new ReflectionClass('\stdClass');
        $this->assertEquals('\stdClass', TypeHelper::resolveType($type, $class));
    }

    public function testResolveTypeForRelativeClassNoRelativeClass() {
        $type = 'stdClass';
        $this->assertEquals('\stdClass', TypeHelper::resolveType($type));
    }

    public function testResolveTypeForAbsoluteClass() {
        $type = '\stdClass';
        $this->assertEquals($type, TypeHelper::resolveType($type));
    }

    public function testResolveTypeForRelativeClass() {
        $type = 'JSONParseException';
        $class = new ReflectionClass(new ObjectMapper());
        $this->assertEquals('\Robtimus\JSON\Mapper\\' . $type, TypeHelper::resolveType($type, $class));
    }

    public function testResolveTypeForRelativeUsedClass() {
        $type = 'TestCase';
        $class = new ReflectionClass('Robtimus\JSON\Mapper\TypeHelperTest');
        $this->assertEquals('\PHPUnit\Framework\TestCase', TypeHelper::resolveType($type, $class));
    }

    public function testResolveTypeForRelativeUsedAliasedClass() {
        $type = 'F\TestCase';
        $class = new ReflectionClass('Robtimus\JSON\Mapper\TypeHelperTest');
        $this->assertEquals('\PHPUnit\Framework\TestCase', TypeHelper::resolveType($type, $class));
    }

    public function testResolveTypeForNonExistingAliasedClass() {
        $type = 'G\TestCase';
        $class = new ReflectionClass('Robtimus\JSON\Mapper\TypeHelperTest');
        try {
            TypeHelper::resolveType($type, $class);
        } catch (JSONMappingException $e) {
            $this->assertEquals('Class not found: \Robtimus\JSON\Mapper\G\TestCase', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }

    public function testResolveTypeForNonExistingClass() {
        $type = '\JSONParseException';
        $class = new ReflectionClass(new ObjectMapper());
        try {
            TypeHelper::resolveType($type, $class);
        } catch (JSONMappingException $e) {
            $this->assertEquals('Class not found: \JSONParseException', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONMappingException');
    }
}
