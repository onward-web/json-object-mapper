<?php
namespace Robtimus\JSON\Mapper;

use Exception;

/**
 * Intermediate base class for all problems encountered when processing (parsing, generating) JSON content.
 */
class JSONProcessingException extends Exception {

    /**
     * Creates a JSONProcessingException from the last occurred JSON error.
     * @return JSONProcessingException
     */
    public static function fromLastJSONError() {
        return new JSONProcessingException(self::getLastJSONErrorMessage(), json_last_error());
    }

    protected static function getLastJSONErrorMessage() {
        if (function_exists('json_last_error_msg')) {
            return json_last_error_msg();
        }
        static $errors = array(
            JSON_ERROR_NONE             => null,
            JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH   => 'State mismatch (invalid or malformed JSON)',
            JSON_ERROR_CTRL_CHAR        => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX           => 'Syntax error',
            JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );
        // no need to define any new constants - they are all added with or after json_last_error_msg
        $error = json_last_error();
        return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
    }
}
