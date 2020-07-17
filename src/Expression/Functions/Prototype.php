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
     *         @SWG\Items(type="string", example="transform")
     *
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
