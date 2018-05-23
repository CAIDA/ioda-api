<?php

namespace App\Expression\Functions;

use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

class PrototypeTag
{
    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="Transform"
     * )
     */
    private $name;

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="Time series transformation functions"
     * )
     */
    private $description;

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
}
