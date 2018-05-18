<?php

namespace App\Entity\Expression;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="\App\Repository\ExpressionFunctionSpecArgumentRepository")
 */
class ExpressionFunctionSpecArgument
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"all"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"all", "public"})
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     * @Groups({"all", "public"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"all", "public"})
     */
    private $type;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"all", "public"})
     */
    private $mandatory;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"all", "public"})
     */
    private $multiple;

    /**
     * @ORM\ManyToOne(targetEntity="ExpressionFunctionSpec", inversedBy="arguments")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"all"})
     */
    private $function;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMandatory(): ?bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory): self
    {
        $this->mandatory = $mandatory;

        return $this;
    }

    public function getMultiple(): ?bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function getFunction(): ?ExpressionFunctionSpec
    {
        return $this->function;
    }

    public function setFunction(?ExpressionFunctionSpec $function): self
    {
        $this->function = $function;

        return $this;
    }
}
