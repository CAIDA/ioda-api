<?php
/**
 * This software is Copyright (c) 2013 The Regents of the University of
 * California. All Rights Reserved. Permission to copy, modify, and distribute this
 * software and its documentation for academic research and education purposes,
 * without fee, and without a written agreement is hereby granted, provided that
 * the above copyright notice, this paragraph and the following three paragraphs
 * appear in all copies. Permission to make use of this software for other than
 * academic research and education purposes may be obtained by contacting:
 *
 * Office of Innovation and Commercialization
 * 9500 Gilman Drive, Mail Code 0910
 * University of California
 * La Jolla, CA 92093-0910
 * (858) 534-5815
 * invent@ucsd.edu
 *
 * This software program and documentation are copyrighted by The Regents of the
 * University of California. The software program and documentation are supplied
 * "as is", without any accompanying services from The Regents. The Regents does
 * not warrant that the operation of the program will be uninterrupted or
 * error-free. The end-user understands that the program was developed for research
 * purposes and is advised not to rely exclusively on the program for any reason.
 *
 * IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE TO ANY PARTY FOR
 * DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING LOST
 * PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS DOCUMENTATION, EVEN IF
 * THE UNIVERSITY OF CALIFORNIA HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE. THE UNIVERSITY OF CALIFORNIA SPECIFICALLY DISCLAIMS ANY WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE. THE SOFTWARE PROVIDED HEREUNDER IS ON AN "AS
 * IS" BASIS, AND THE UNIVERSITY OF CALIFORNIA HAS NO OBLIGATIONS TO PROVIDE
 * MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
 */

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
