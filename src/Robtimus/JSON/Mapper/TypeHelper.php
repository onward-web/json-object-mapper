<?php
namespace Robtimus\JSON\Mapper;

use InvalidArgumentException;
use ReflectionClass;
use Doctrine\Common\Annotations\PhpParser;

/**
 * @internal Helper class for types.
 */
final class TypeHelper {

    private static $phpParser = null;
    private static $useStatements = array();

    static function normalizeType($type, ReflectionClass $relativeTo = null) {
        if ($type instanceof ReflectionClass) {
            return $type->getName();
        }
        if (!is_string($type)) {
            throw new InvalidArgumentException('type must be a type name or ReflectionClass');
        }
        return TypeHelper::resolveType($type, $relativeTo);
    }

    static function resolveType($type, ReflectionClass $relativeTo = null) {
        if (substr($type, -2) === '[]') {
            return self::resolveType(substr($type, 0, -2), $relativeTo) . '[]';
        }
        if (self::isScalarType($type)) {
            return $type;
        }
        if ($type[0] !== '\\') {
            if (is_null($relativeTo)) {
                $type = '\\' . $type;
            } else {
                if (is_null(self::$phpParser)) {
                    self::$phpParser = new PhpParser();
                }
                if (!array_key_exists($relativeTo->getName(), self::$useStatements)) {
                    self::$useStatements[$relativeTo->getName()] = self::$phpParser->parseClass($relativeTo);
                }
                $key = strtolower($type);
                if (array_key_exists($key, self::$useStatements[$relativeTo->getName()])) {
                    $type = self::$useStatements[$relativeTo->getName()][$key];
                } else {
                    $index = strpos($type, '\\');
                    if ($index === false) {
                        $namespace = $relativeTo->getNamespaceName();
                        $prefix = $namespace;
                        $type = $prefix . '\\' . $type;
                    } else {
                        $prefix = strtolower(substr($type, 0, $index));
                        if (array_key_exists($prefix, self::$useStatements[$relativeTo->getName()])) {
                            $type = self::$useStatements[$relativeTo->getName()][$prefix] . substr($type, $index);
                        } else {
                            $namespace = $relativeTo->getNamespaceName();
                            $prefix = $namespace;
                            $type = $prefix . '\\' . $type;
                        }
                    }
                }
                if ($type[0] !== '\\') {
                    $type = '\\' . $type;
                }
            }
        }
        if (class_exists($type)) {
            return $type;
        }
        throw new JSONMappingException("Class not found: $type");
    }

    static function isScalarType($type) {
        static $simpleTypes = array('boolean', 'bool', 'integer', 'int', 'float', 'double', 'string', 'long');
        return in_array($type, $simpleTypes, true);
    }
}
