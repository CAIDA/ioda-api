<?php

namespace App\Expression\Functions;


use App\Expression\Functions\Prototype;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

class Registry
{
    /**
     * @SWG\Property(
     *     type="array",
     *     @SWG\Items(ref=@Model(type=PrototypeTag::class, groups={"public"}))
     * )
     * @Groups({"public"})
     */
    private $tags;

    /**
     * @SWG\Property(
     *     type="object",
     *     additionalProperties={
     *         "$ref": "#/definitions/Prototype"
     *     }
     * )
     * @Groups({"public"})
     */
    private $prototypes;

    public function __construct()
    {
        $this->prototypes = [];
        $this->tags = [];
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return Prototype[]
     */
    public function getPrototypes(): array
    {
        return $this->prototypes;
    }
}