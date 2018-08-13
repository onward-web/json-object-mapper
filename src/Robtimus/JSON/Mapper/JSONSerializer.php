<?php
namespace Robtimus\JSON\Mapper;

/**
 * Interface that defines the API used by ObjectMapper to serialize objects of arbitrary types into JSON.
 */
interface JSONSerializer {

    /**
     * Serializes the given value to a JSON value.
     * The return value should be a primitive type, stdClass instance, or an array of primitive types or stdClass instances.
     * @param mixed $value
     * @return mixed
     * @throws JSONGenerationException If the value could not be converted to JSON.
     */
    function toJSON($value);
}
