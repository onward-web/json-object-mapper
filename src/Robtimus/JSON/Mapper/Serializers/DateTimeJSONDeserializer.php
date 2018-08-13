<?php
namespace Robtimus\JSON\Mapper\Serializers;

use DateTimeZone;
use Robtimus\JSON\Mapper\JSONDeserializer;
use Robtimus\JSON\Mapper\JSONParseException;

/**
 * A JSON deserializer for DateTime and DateTimeImmutable objects.
 */
class DateTimeJSONDeserializer implements JSONDeserializer {

    /**
     * @var string
     */
    private $format;

    /**
     * @var DateTimeZone|null
     */
    private $timeZone;

    /**
     * @param string $format
     * @param DateTimeZone|null $timeZone
     */
    public function __construct($format = \DateTime::ATOM, DateTimeZone $timeZone = null) {
        $this->format = $format;
        $this->timeZone = $timeZone;
    }

    public function fromJSON($value, $type) {
        switch ($type) {
            case 'DateTime':
            case '\DateTime':
                return $this->getDateTimeFromJSON($value);
            case 'DateTimeImmutable':
            case '\DateTimeImmutable':
                if (class_exists('\DateTimeImmutable')) {
                    return $this->getDateTimeImmutableFromJSON($value);
                }
                // else continue
        }
        throw new JSONParseException("Unsupported type: $type");
    }

    private function getDateTimeFromJSON($value) {
        $result = is_null($this->timeZone)
            ? \DateTime::createFromFormat($this->format, $value)
            : \DateTime::createFromFormat($this->format, $value, $this->timeZone);

        if ($result !== FALSE) {
            return $result;
        }
        $lastErrors = \DateTime::getLastErrors();
        $message = "Could not parse '$value' into a DateTime object";
        if (array_key_exists('errors', $lastErrors)) {
            $message = implode(', ', $lastErrors['errors']);
        }
        throw new JSONParseException($message);
    }

    private function getDateTimeImmutableFromJSON($value) {
        $result = is_null($this->timeZone)
            ? \DateTimeImmutable::createFromFormat($this->format, $value)
            : \DateTimeImmutable::createFromFormat($this->format, $value, $this->timeZone);

        if ($result !== FALSE) {
            return $result;
        }
        $lastErrors = \DateTimeImmutable::getLastErrors();
        $message = "Could not parse '$value' into a DateTimeImmutable object";
        if (array_key_exists('errors', $lastErrors)) {
            $message = implode(', ', $lastErrors['errors']);
        }
        throw new JSONParseException($message);
    }
}
