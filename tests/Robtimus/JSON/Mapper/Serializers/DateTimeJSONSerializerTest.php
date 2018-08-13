<?php
namespace Robtimus\JSON\Mapper\Serializers;

use PHPUnit\Framework\TestCase;
use Robtimus\JSON\Mapper\JSONGenerationException;

class DateTimeJSONSerializerTest extends TestCase {

    public function testToJSONDateTime() {
        $serializer = new DateTimeJSONSerializer();

        $dateTime = new \DateTime();
        $dateTime->setDate(2018, 12, 31);
        $dateTime->setTime(12, 13, 14);

        $result = $serializer->toJSON($dateTime);

        $this->assertStringStartsWith('2018-12-31T12:13:14', $result);
    }

    public function testToJSONDateTimeImmutable() {
        if (!class_exists('DateTimeImmutable')) {
            $this->markTestSkipped('DateTimeImmutable is not available');
        }

        $serializer = new DateTimeJSONSerializer();

        $dateTime = new \DateTime();
        $dateTime->setDate(2018, 12, 31);
        $dateTime->setTime(12, 13, 14);
        $dateTime = \DateTimeImmutable::createFromMutable($dateTime);

        $result = $serializer->toJSON($dateTime);

        $this->assertStringStartsWith('2018-12-31T12:13:14', $result);
    }

    public function testToJSONUnsupportedType() {
        $serializer = new DateTimeJSONSerializer();

        $value = 'foo';

        try {
            $serializer->toJSON($value);
        } catch (JSONGenerationException $e) {
            $this->assertEquals("Unsupported value '$value' of type string", $e->getMessage());
            return;
        }
        $this->fail('Expected a JSONGenerationException');
    }
}
