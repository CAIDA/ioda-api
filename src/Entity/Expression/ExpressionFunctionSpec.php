<?php

namespace App\Entity\Expression;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="\App\Repository\ExpressionFunctionSpecRepository")
 */
class ExpressionFunctionSpec
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
     * @SWG\Property(
     *     type="string",
     *     example="sumSeries"
     * )
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"all", "public"})
     * @SWG\Property(
     *     type="string",
     *     example="Sum Series"
     * )
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="ExpressionFunctionSpecArgument", mappedBy="function", orphanRemoval=true)
     * @Groups({"all", "public"})
     * @SWG\Property(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=\App\Entity\Expression\ExpressionFunctionSpecArgument::class, groups={"public"}))
     * )
     */
    private $arguments;

    /**
     * @ORM\ManyToMany(targetEntity="ExpressionFunctionSpecTag", inversedBy="functions")
     * @Groups({"all", "public"})
     * @SWG\Property(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=\App\Entity\Expression\ExpressionFunctionSpecTag::class, groups={"public"}))
     * )
     */
    private $tags;

    public function __construct()
    {
        $this->arguments = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
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

    /**
     * @return Collection|ExpressionFunctionSpecArgument[]
     */
    public function getArguments(): Collection
    {
        return $this->arguments;
    }

    public function addArgument(ExpressionFunctionSpecArgument $argument): self
    {
        if (!$this->arguments->contains($argument)) {
            $this->arguments[] = $argument;
            $argument->setFunction($this);
        }

        return $this;
    }

    public function removeArgument(ExpressionFunctionSpecArgument $argument): self
    {
        if ($this->arguments->contains($argument)) {
            $this->arguments->removeElement($argument);
            // set the owning side to null (unless already changed)
            if ($argument->getFunction() === $this) {
                $argument->setFunction(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ExpressionFunctionSpecTag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(ExpressionFunctionSpecTag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(ExpressionFunctionSpecTag $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }

        return $this;
    }
}
