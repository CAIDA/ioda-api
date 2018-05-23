<?php

namespace App\Expression\Functions;

use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

class PrototypeParameter
{
    public static $TYPE_TIME_SERIES = 'timeSeries';
    public static $TYPE_STRING = 'string';
    public static $TYPE_NUMBER = 'number';

    /**
     * @Groups({"public"})
     */
    private $name;

    /**
     * @Groups({"public"})
     */
    private $description;

    /**
     * @Groups({"public"})
     * @SWG\Schema(
     *     type="string",
     *     enum={"timeSeries", "string", "number"}
     * )
     */
    private $type;

    /**
     * @Groups({"public"})
     */
    private $mandatory;

    /**
     * @Groups({"public"})
     */
    private $multiple;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory): void
    {
        $this->mandatory = $mandatory;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): void
    {
        $this->multiple = $multiple;
    }

}
