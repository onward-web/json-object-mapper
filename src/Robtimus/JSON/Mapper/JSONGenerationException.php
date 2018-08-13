<?php
namespace Robtimus\JSON\Mapper;

/**
 * Exception type for exceptions during JSON writing.
 */
class JSONGenerationException extends JSONProcessingException {

    /**
     * Creates a JSONGenerationException from the last occurred JSON error.
     * @return JSONGenerationException
     */
    public static function fromLastJSONError() {
        return new JSONGenerationException(parent::getLastJSONErrorMessage(), json_last_error());
    }
}
