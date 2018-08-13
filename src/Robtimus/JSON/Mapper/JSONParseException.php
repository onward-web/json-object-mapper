<?php
namespace Robtimus\JSON\Mapper;

/**
 * Exception type for parsing problems.
 */
class JSONParseException extends JSONProcessingException {

    /**
     * Creates a JSONParseException from the last occurred JSON error.
     * @return JSONParseException
     */
    public static function fromLastJSONError() {
        return new JSONParseException(parent::getLastJSONErrorMessage(), json_last_error());
    }
}
