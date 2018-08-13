# JSON Object Mapper
Converts objects to and from JSON (serialization and deserialization respectively). Although built upon [json_encode](http://php.net/manual/en/function.json-encode.php) and [json_decode](http://php.net/manual/en/function.json-decode.php), it supports any type of object, not just [stdClass](http://php.net/manual/en/reserved.classes.php).

## Basic usage
An `ObjectMapper` instance has two methods:
* `toJSON` takes an object or array and serializes it to a JSON string.
* `fromJSON` takes a JSON string and deserializes it to an object or array of objects. The type of object must be given, either as its fully qualified name, or as an [ReflectionClass](http://php.net/manual/en/class.reflectionclass.php) instance).

Example:

    $mapper = new ObjectMapper();
    $json = $mapper->toJSON($object);
    $object = $mapper->fromJSON($json, '\MyClass');

## JSON Property mapping
By default, all public properties, getters (both `get` and `is` prefixes are supported) and setters are included. These need to specify their types using [phpDocumenter](https://docs.phpdoc.org/) annotations. For instance:

    class Person {
        /**
         * @var string
         */
        public $firstName;

        private $lastName;

        /**
         * @return string
         */
        public function getLastName() {
            return $this->lastName;
        }

        /**
         * @param string $value
         */
        public function setLastName($value) {
            $this->lastName = $value;
        }
    }

The following are supported:
* Scalar types (`boolean`, `bool`, `integer`, `int`, `float`, `double`, `string`).
* Object types.
* `\stdClass`.
* Arrays of the above; append `[]` after the type.

### Including and excluding JSON properties

Note: all of the below annotations are located in namespace `Robtimus\JSON\Mapper\Annotations`, and either need to be included with a `use` statement, or be used with their fully qualified class names.

#### @JSONAccessorType
A class can be annotated with `@JSONAccessorType("<ACCESSOR_TYPE>")` to change what is automatically included. The following are valid accessor types:
* `METHOD`: all getters and setters are included, even if they are not public.
* `PROPERTY`: all properties are included, even if they are not public.
* `PUBLIC_MEMBER`: all public properties, getters and setters are included.
* `NONE`: no properties, getters or setters are included unless explicitly included. Use this if you want to explicitly include JSON properties.

#### @JSONProperty
A single property, getter or setter can be explicitly included by annotating it with `@JSONProperty`, even if it would otherwise be excluded. This annotation can take an optional `name` property to override the default property name: `@JSONProperty(name = "name")`.

If no explicit JSON property name is given it is determined as follows:
* For properties, the property name.
* For getters and setters, the getter/setter name with the leading `get`, `is` or `set` prefix and any underscores following it immediately removed, then the first character lowercased. If the method name after the prefix is all uppercase it will be considered an acronym and be completely lowercased (e.g. `getURL` => `url`).

#### @JSONIgnore
A single property, getter or setter can be ignored by annotating it with `@JSONIgnore`, even if it would otherwise be included (this overrides `@JSONProperty`).

#### @JSONReadOnly
A single property can be made read-only (i.e. it will be ignored during deserialization) by annotating it with `@JSONReadOnly`. The same can also be achieved by making the property private and only providing a getter, or by annotating the setter with `@JSONIgnore`.

#### @JSONWriteOnly
A single property can be made write-only (i.e. it will be ignored during serialization) by annotating it with `@JSONWriteOnly`. The same can also be achieved by making the property private and only providing a setter, or by annotating the getter with `@JSONIgnore`.

#### @JSONAnyGetter
A single method that takes no arguments and returns an associative array, `stdClass` instance or an iterator can be annotated with `@JSONAnyGetter`. The keys and values of the return value will be added to the JSON string during serialization.

#### Ignoring `null` values and/or empty arrays
Set the `omitNullValues` property of an `ObjectMapper` instance to `true` to omit JSON properties with `null` values.

Set the `omitEmptyArrays` property of an `ObjectMapper` instance to `true` to omit JSON properties with empty array values.

### JSON Property order
By default, JSON properties will have an undetermined order during serialization. A class can be annotated with `@JSONPropertyOrder` to create a specific order. This annotation can be used in two ways:
* `@JSONPropertyOrder(alphabetical = true)` to use alphabetical ordering.
* `@JSONPropertyOrder(properties = {"property1", "property2", ...})` to specify the desired order. Note that this list must include all JSON properties, including inherited JSON properties.

Note that this property order does not include properties returned by methods annotated with `@JSONAnyGetter`.

### Handling unknown properties
By default, when deserializing a JSON string to an object, any JSON property that is not defined in the object will cause a `JSONParseException` to be thrown. This can be be prevented in one of two ways:
* Set the `allowUndefinedProperties` property of the `ObjectMapper` instance to true. This will cause any undefined JSON property to be ignored.
* Provide a single method that takes two arguments (for the JSON property name and its value), and annotate it with `@JSONAnySetter`. This method will be called for each unknown JSON property and its value.

### Custom serialization and deserialization
By default, scalar values are used as-is, and object are processed as described above. This behaviour can be changed by providing a custom serializer or deserializer.

#### Custom serialization
A custom serializer can be created by implementing `JSONSerializer`, and can be defined for a JSON property in two ways:
* Annotate a property or getter with `@JSONSerialize(using = "<JSONSerializer class name>")` to use a custom serializer for a single property or getter.
* Call `setDefaultSerializer` on the `ObjectMapper` instance to use a custom serializer for all JSON properties of a specific type. For example, to use a `DateTimeJSONSerializer` with for all `DateTime` JSON properties:

    `$mapper->setDefaultSerializer('\DateTime', new DateTimeJSONSerializer(DateTime::RFC3339_EXTENDED));`

#### Custom serialization
A custom deserializer can be created by implementing `JSONDeserializer`, and can be defined for a JSON property in two ways:
* Annotate a property or setter with `@JSONDeserialize(using = "<JSONDeserializer class name>")` to use a custom deserializer for a single property or setter.
* Call `setDefaultDeserializer` on the `ObjectMapper` instance to use a custom deserializer for all JSON properties of a specific type. For example, to use a `DateTimeJSONDeserializer` with for all `DateTime` JSON properties:

    `$mapper->setDefaultDeserializer('\DateTime', new DateTimeJSONDeserializer(DateTime::RFC3339_EXTENDED));`

### Programmatic mapping
Besides using annotations, mappings for a class can also be created programmatically by creating a `ClassDescriptor` and adding properties. It can then be added to an `ObjectMapper` instance using its `registerClass` method. 

The following is a mapping of annotations to the methods that replace them:
* `@JSONAccessorType`: no replacement, as programmatic mapping replaces all automatic inclusion of JSON properties.
* `@JSONProperty`: call `addProperty` or `ensureProperty` on a `ClassDescriptor` instance, these returns the added property as an instance of `PropertyDescriptor`. The name and type are required; the type can be relative to an optional third argument of type `ReflectionClass`.
* `@JSONIgnore`: no replacement, as programmatic mapping only properties to include can be added.
* `@JSONReadOnly`: do not call `withSetter` on an added property.
* `@JSONWriteOnly`: do not call `withGetter` on an added property.
* `@JSONAnyGetter`: call `withAnyGetter` on a `ClassDescriptor` instance. This method is additive, so it's possible to specify multiple methods.
* `@JSONAnySetter`: call `withAnySetter` on a `ClassDescriptor` instance.
* `@JSONPropertyOrder`: call `orderProperties` on a `ClassDescriptor` instance. Provide an array with the JSON properties to order on, or provide no arguments to use alphatical ordering.
* `@JSONSerialize`: call `withSerializer` on an added property.
* `@JSONDeserialize`: call `withDeserializer` on an added property.

Note that all getters and setters are callables. This removes the requirement to use instance methods.

An example:

    $classDescriptor = new ClassDescriptor(new ReflectionClass('\Person'));
    $classDescriptor->addProperty('firstName', 'string')
        ->withGetter(array(new ReflectionProperty('\Person', 'firstName'), 'getValue'))
        ->withSetter(array(new ReflectionProperty('\Person', 'firstName'), 'setValue'));
    $classDescriptor->addProperty('lastName', 'string')
        ->withGetter(array(new ReflectionMethod('\Person', 'getLastName'), 'invoke'))
        ->withSetter(array(new ReflectionMethod('\Person', 'setLastName'), 'invoke'));
    $mapper->registerClass($classDescriptor);

## Formatting
Similar to [json_encode](http://php.net/manual/en/function.json-encode.php), the `toJSON` method of an `ObjectMapper` instance can take a bitmask of options. The same options are supported.
