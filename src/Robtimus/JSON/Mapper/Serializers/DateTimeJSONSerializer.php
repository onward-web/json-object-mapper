<?php
namespace Robtimus\JSON\Mapper\Serializers;

use Exception;
use Robtimus\JSON\Mapper\JSONGenerationException;
use Robtimus\JSON\Mapper\JSONSerializer;

/**
 * A JSON serializer for DateTime and DateTimeImmutable objects.
 */
class DateTimeJSONSerializer implements JSONSerializer {

    /**
     * @var string
     */
    private $format;

    /**
     * @param string $format
     */
    public function __construct($format = \DateTime::ATOM) {
        $this->format = $format;
    }

    public function toJSON($value) {
        if (!is_object($value) || (!is_a($value, '\DateTime') && !is_a($value, '\DateTimeImmutable'))) {
            $valueType = gettype($value);
            if (is_scalar($value)) {
                throw new JSONGenerationException("Unsupported value '$value' of type $valueType");
            } else {
                throw new JSONGenerationException("Unsupported value of type $valueType");
            }
            throw new JSONGenerationException();
        }
        
        $result = $value->format($this->format);
        if ($result !== FALSE) {
            return $result;
        }
        $lastErrors = is_a($value, '\DateTime') ? \DateTime::getLastErrors() : \DateTimeImmutable::getLastErrors();
        $message = "Could not parse '$value' into a DateTime object";
        if (array_key_exists('errors', $lastErrors)) {
            $message = implode(', ', $lastErrors['errors']);
        }
        throw new JSONGenerationException($message);
    }
}
