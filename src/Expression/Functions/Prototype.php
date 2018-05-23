<?php

namespace App\Expression\Functions;

use App\Expression\Functions\PrototypeParameter;
use App\Expression\Functions\PrototypeTag;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class Prototype
 * @package App\Expression\Functions
 */
class Prototype
{
    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="sumSeries"
     * )
     */
    private $name;

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="Sum Series"
     * )
     */
    private $title;

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *     type="string",
     *     example="Adds metrics together and returns the sum at each datapoint."
     * )
     */
    private $description;

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=PrototypeParameter::class, groups={"public"}))
     * )
     */
    private $parameters;

    /**
     * @Groups({"public"})
     * @SWG\Property(
     *         type="array",
     *         @SWG\Items(type="string")
     * )
     */
    private $tags;

    public function __construct($name, $title, $description)
    {
        $this->setName($name);
        $this->setTitle($title);
        $this->setDescription($description);
        $this->parameters = [];
        $this->tags = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return PrototypeParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param PrototypeParameter[] $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * @return PrototypeTag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param PrototypeTag[] $tags
     */
    public function setTags($tags): void
    {
        $this->tags = $tags;
    }


}
