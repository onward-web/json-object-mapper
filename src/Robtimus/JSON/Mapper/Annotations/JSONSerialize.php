<?php
namespace Robtimus\JSON\Mapper\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class JSONSerialize {

    /**
     * @var string
     * @Required
     */
    public $using;
}
