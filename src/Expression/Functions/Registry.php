<?php

namespace App\Expression\Functions;


use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;
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
        foreach ($parsed['tags'] as $tagId => $rawTag) {
            $this->tags[$tagId] = new PrototypeTag($rawTag['name'], $rawTag['description'] ?? null);
        }
        foreach ($parsed['prototypes'] as $name => $rawPrototype) {
            $this->prototypes[$name] = $proto = new Prototype(
                $name,
                $rawPrototype['name'],
                $rawPrototype['description']
            );
            $proto->setTags($rawPrototype['tags']);
            $params = [];
            foreach ($rawPrototype['parameters'] as $rawParam) {
                $params[] = new PrototypeParameter(
                    $rawParam['name'],
                    $rawParam['description'],
                    $rawParam['type'],
                    $rawParam['mandatory'],
                    $rawParam['multiple'] ?? false
                );
            }
            $proto->setParameters($params);
        }
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
