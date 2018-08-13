<?php
namespace Robtimus\JSON\Mapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class JSONAccessorType {

    /** Indicates all getters and setters are included, even non-public ones */
    const ACCESSOR_TYPE_METHOD        = 'METHOD';
    /** Indicates all properties are included, even non-public ones */
    const ACCESSOR_TYPE_PROPERTY      = 'PROPERTY';
    /** Indicates all public properties, getters and setters are included */
    const ACCESSOR_TYPE_PUBLIC_MEMBER = 'PUBLIC_MEMBER';
    /** Indicates no properties, getters or setters are included unless explicitly included */
    const ACCESSOR_TYPE_NONE          = 'NONE';

    /**
     * @var string
     * @Required
     * @Enum({"METHOD", "PROPERTY", "PUBLIC_MEMBER", "NONE"})
     */
    public $value;
}
