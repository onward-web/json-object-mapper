<?php
namespace Robtimus\JSON\Mapper\Descriptors;

use ReflectionClass;
use Robtimus\JSON\Mapper\TypeHelper;
use Robtimus\JSON\Mapper\JSONSerializer;
use Robtimus\JSON\Mapper\JSONDeserializer;

/**
 * A descriptor for a single JSON property.
 */
final class PropertyDescriptor {

    /**
     * @var string
     */
    private $type;

    /**
     * @var callable|null
     */
    private $getter = null;

    /**
     * @var callable|null
     */
    private $setter = null;

    /**
     * @var JSONSerializer|null
     */
    private $serializer = null;

    /**
     * @var JSONDeserializer|null
     */
    private $deserializer = null;

    private function __construct($type) {
        $this->type = $type;
    }

    /**
     * @param PropertyDescriptor $descriptor
     * @return PropertyDescriptor A copy of the given property descriptor
     */
    public static function copy(PropertyDescriptor $descriptor) {
        $copy = new PropertyDescriptor($descriptor->type);
        $copy->getter       = $descriptor->getter;
        $copy->setter       = $descriptor->setter;
        $copy->serializer   = $descriptor->serializer;
        $copy->deserializer = $descriptor->deserializer;
        return $copy;
    }

    /**
     * @param ReflectionClass|string $type The property type
     * @param ReflectionClass|null $relativeTo An optional class that the given type is relative to
     * @return PropertyDescriptor A new property descriptor of the given type
     */
    public static function create($type, ReflectionClass $relativeTo = null) {
        $type = TypeHelper::normalizeType($type, $relativeTo);

        return new PropertyDescriptor($type);
    }

    /**
     * @param callable|null $getter A callable that takes as argument an object and returns for the value for this object
     * @return PropertyDescriptor $this
     */
    public function withGetter(callable $getter = null) {
        $this->getter = $getter;
        return $this;
    }

    /**
     * @param callable|null $setter A callable that takes as argument an object and value and sets this value for this object
     * @return PropertyDescriptor $this
     */
    public function withSetter(callable $setter = null) {
        $this->setter = $setter;
        return $this;
    }

    /**
     * @param JSONSerializer|null $serializer The JSON serializer to use
     * @return PropertyDescriptor $this
     */
    public function withSerializer(JSONSerializer $serializer = null) {
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * @param JSONDeserializer|null $deserializer The JSON deserializer to use
     * @return PropertyDescriptor $this
     */
    public function withDeserializer(JSONDeserializer $deserializer = null) {
        $this->deserializer = $deserializer;
        return $this;
    }

    /**
     * @return string
     * @internal
     */
    public function type() {
        return $this->type;
    }

    /**
     * @return callable|null
     * @internal
     */
    public function getter() {
        return $this->getter;
    }

    /**
     * @return callable|null
     * @internal
     */
    public function setter() {
        return $this->setter;
    }

    /**
     * @return JSONSerializer|null
     * @internal
     */
    public function serializer() {
        return $this->serializer;
    }

    /**
     * @return JSONDeserializer|null
     * @internal
     */
    public function deserializer() {
        return $this->deserializer;
    }
}
