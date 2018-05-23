<?php

namespace App\Expression\Functions;


use App\Expression\Functions\Prototype;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Yaml\Parser as YamlParser;

class Registry
{
    private static $YAML_REGISTRY_FILE = __DIR__.'/registry.yaml';

    /**
     * @SWG\Property(
     *     type="object",
     *     additionalProperties={
     *         @SWG\Property(ref=@Model(type=PrototypeTag::class, groups={"public"})),
     *         "$ref": "#/definitions/PrototypeTag"
     *     }
     * )
     * @Groups({"public"})
     */
    private $tags;

    /**
     * @SWG\Property(
     *     type="object",
     *     additionalProperties={
     *         @SWG\Property(ref=@Model(type=Prototype::class, groups={"public"})),
     *         "$ref": "#/definitions/Prototype"
     *     }
     * )
     * @Groups({"public"})
     */
    private $prototypes;

    public function __construct()
    {
        $yamlParser = new YamlParser();
        $parsed = $yamlParser->parse(file_get_contents(Registry::$YAML_REGISTRY_FILE));
        $this->prototypes = [];
        $this->tags = [];
        $this->loadTags($parsed['tags']);
        $this->loadPrototypes($parsed['prototypes']);
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    private function loadTags($rawTags)
    {
        foreach ($rawTags as $tagId => $rawTag) {
            $this->tags[$tagId] = new PrototypeTag($rawTag['name'], $rawTag['description'] ?? null);
        }
    }

    /**
     * @return Prototype[]
     */
    public function getPrototypes(): array
    {
        return $this->prototypes;
    }

    private function loadPrototypes($rawPrototypes)
    {

    }
}