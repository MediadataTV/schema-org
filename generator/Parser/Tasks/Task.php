<?php

namespace Spatie\SchemaOrg\Generator\Parser\Tasks;

use Tightenco\Collect\Support\Collection;

abstract class Task
{
    /** @array */
    protected $definition;

    public function __construct(array $definition)
    {
        $this->definition = $definition;
    }

    public static function fromJsonLdArray(array $jsonLdArray): self
    {
        return new static($jsonLdArray);
    }

    protected function getDefinitionProperty(string $selector): string
    {
        return $this->definition[$selector] ?? '';
    }

    protected function getWrappedDefinitionProperty(string $selector): Collection
    {
        $property = $this->definition[$selector] ?? [];
        if (count($property) === 1) {
            $property = [$property];
        }

        return collect($property);
    }

    /**
     * Get the schema resource URL.
     */
    protected function getResource(?array $schemaResource = null)
    {
        return $schemaResource['@id'] ?? $this->definition['@id'];
    }

    /**
     * Get the Schema resource name.
     *
     * Currently this omits the schema.org domain "http://scheme.org/"
     */
    protected function getResourceName(?array $schemaResource = null)
    {
        return substr($this->getResource($schemaResource), 18);
    }
}
