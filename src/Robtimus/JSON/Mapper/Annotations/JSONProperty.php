<?php
namespace Robtimus\JSON\Mapper\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class JSONProperty {

    /**
     * @var string
     */
    public $name = '';
}
