<?php
namespace Robtimus\JSON\Mapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class JSONPropertyOrder {

    /**
     * @var bool
     */
    public $alphabetical = false;

    /**
     * @var array<string>
     */
    public $properties;
}
