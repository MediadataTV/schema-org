<?php

namespace Spatie\SchemaOrg;

use ArrayAccess;
use DateTime;
use DateTimeInterface;
use JsonSerializable;
use ReflectionClass;
use Spatie\SchemaOrg\Exceptions\InvalidProperty;

abstract class BaseType implements Type, ArrayAccess, JsonSerializable
{
    /** @var array */
    protected $properties = [];

    protected static $contentTypes = [
        "Movie",
        "TVSeries",
        "TVSeason",
        "TVEpisode",
        "Event",
        "MusicEvent",
        "SportsEvent"
    ];

    public function getContext(): string
    {
        return 'https://schema.org';
    }

    public function getType(): string
    {
        return substr(strrchr(get_class($this), '\\'), 1);
    }

    public function setProperty(string $property, $value)
    {
        if ($value !== null && $value !== '') {
            $this->properties[$property] = $value;
        }

        return $this;
    }

    public function addProperties(array $properties)
    {
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }

        return $this;
    }

    public function if($condition, $callback)
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    public function getProperty(string $property, $default = null)
    {
        return $this->properties[$property] ?? $default;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return ReferencedType|static
     */
    public function referenced()
    {
        return new ReferencedType($this);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->properties);
    }

    public function offsetGet($offset)
    {
        return $this->getProperty($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setProperty($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    public function toArray(): array
    {
        $type = $this->getType();
        $this->serializeIdentifier($type);
        $properties = $this->serializeProperty($this->getProperties());

        return [
            '@context' => $this->getContext(),
            '@type' => $type,
        ] + $properties;
    }

    protected function serializeProperty($property)
    {
        if (is_array($property)) {
            return array_map([$this, 'serializeProperty'], $property);
        }

        if ($property instanceof Type) {
            $property = $property->toArray();
            unset($property['@context']);
        }

        if ($property instanceof DateTimeInterface) {
            $property = $property->format(DateTime::ATOM);
        }

        if (is_object($property) && method_exists($property, '__toString')) {
            $property = (string) $property;
        }

        if (is_object($property)) {
            throw new InvalidProperty();
        }

        return $property;
    }

    protected function serializeIdentifier($type)
    {
        // Try to avoid magic identifier -> @id switching
        if (in_array($type,self::$contentTypes)) {
            return false;
        }

        if (
            isset($this['identifier'])
            && ! $this['identifier'] instanceof Type
        ) {
            $this->setProperty('@id', $this['identifier']);
            unset($this['identifier']);
        }
    }

    public function toScript(): string
    {
        return '<script type="application/ld+json">'.json_encode($this->toArray(), JSON_UNESCAPED_UNICODE).'</script>';
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function __call(string $method, array $arguments)
    {
        return $this->setProperty($method, $arguments[0] ?? '');
    }

    public function __toString(): string
    {
        return $this->toScript();
    }
}
