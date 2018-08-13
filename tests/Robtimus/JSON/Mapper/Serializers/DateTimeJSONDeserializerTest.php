<?php
namespace Robtimus\JSON\Mapper\Serializers;

use PHPUnit\Framework\TestCase;
use Robtimus\JSON\Mapper\JSONParseException;

class DateTimeJSONDeserializerTest extends TestCase {

    public function testFromJSONDateTime() {
        $deserializer = new DateTimeJSONDeserializer();

        $value = '2018-12-31T12:13:14+0100';

        $result = $deserializer->fromJSON($value, 'DateTime');

        $this->assertInstanceOf('\DateTime', $result);
        $this->assertStringStartsWith('2018-12-31T12:13:14', $result->format(\DateTime::ATOM));
    }

    public function testFromJSONDateTimeImmutable() {
        if (!class_exists('DateTimeImmutable')) {
            $this->markTestSkipped('DateTimeImmutable is not available');
        }

        $deserializer = new DateTimeJSONDeserializer();

        $value = '2018-12-31T12:13:14+0100';

        $result = $deserializer->fromJSON($value, 'DateTimeImmutable');

        $this->assertInstanceOf('\DateTimeImmutable', $result);
        $this->assertStringStartsWith('2018-12-31T12:13:14', $result->format(\DateTime::ATOM));
    }

    public function testFromJSONInvalidValue() {
        $deserializer = new DateTimeJSONDeserializer();

        $value = '2018-12-31T12:13:14';

        try {
            $deserializer->fromJSON($value, 'DateTime');
        } catch (JSONParseException $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertNotEquals("Could not parse '$value' into a DateTime object", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONParseException');
    }

    public function testFromJSONUnsupportedType() {
        $deserializer = new DateTimeJSONDeserializer();

        $value = '2018-12-31T12:13:14';

        try {
            $deserializer->fromJSON($value, 'stdClass');
        } catch (JSONParseException $e) {
            $this->assertEquals('Unsupported type: stdClass', $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONParseException');
    }
}
