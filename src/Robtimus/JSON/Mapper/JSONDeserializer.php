<?php
namespace Robtimus\JSON\Mapper;

/**
 * Interface that defines the API used by ObjectMapper to deserialize objects of arbitrary types from JSON.
 */
interface JSONDeserializer {

    /**
     * Deserializes the given value from a JSON value.
     * @param mixed $value A primitive, stdClass instance, or an array of primitive types or stdClass instances.
     * @param string $type The expected return type.
     * @return mixed
     * @throws JSONParseException If the value could not be converted from JSON.
     */
    function fromJSON($value, $type);
}
