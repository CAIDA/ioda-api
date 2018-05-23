<?php

namespace App\Expression\Functions;

use Symfony\Component\Serializer\Annotation\Groups;

class PrototypeTag
{
    /**
     * @Groups({"public"})
     */
    private $name;

    /**
     * @Groups({"public"})
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
