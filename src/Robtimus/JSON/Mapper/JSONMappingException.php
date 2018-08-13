<?php
namespace Robtimus\JSON\Mapper;

/**
 * Exception used to signal fatal problems with mapping of content.
 */
class JSONMappingException extends JSONProcessingException {

    /**
     * Creates a JSONMappingException from the last occurred JSON error.
     * @return JSONMappingException
     */
    public static function fromLastJSONError() {
        return new JSONMappingException(parent::getLastJSONErrorMessage(), json_last_error());
    }
}
