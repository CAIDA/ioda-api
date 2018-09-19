<?php

namespace App\TimeSeries\Annotation;


use Symfony\Component\Serializer\Annotation\Groups;

abstract class AbstractAnnotation
{
    /**
     * @var array
     * @Groups({"public"})
     */
    private $attributes;

    public function __construct()
    {
        $this->attributes = [];
    }

    /**
     * @Groups({"public"})
     */
    abstract public function getType(): string;

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string key
     * @param mixed value
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @param string key
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        } else {
            return null;
        }
    }
}
