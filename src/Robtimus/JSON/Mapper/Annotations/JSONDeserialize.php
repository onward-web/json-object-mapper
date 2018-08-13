<?php
namespace Robtimus\JSON\Mapper\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class JSONDeserialize {

    /**
     * @var string
     * @Required
     */
    public $using;
}
