<?php

namespace App\Entity\Outages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="mddb_entity_attribute")
 */
class MetadataEntityAttribute
{

    //////////////////////////
    //////////////////////////
    // VARIABLE DEFINITIONS //
    //////////////////////////
    //////////////////////////

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @Groups({"public"})
     * @ORM\Column(type="string")
     * @var string
     */
    private $key;

    /**
     * @Groups({"public"})
     * @ORM\Column(type="string")
     * @var string
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="MetadataEntity")
     * @ORM\JoinColumn(name="metadata_id", referencedColumnName="id")
     * @var MetadataEntity
     */
    private $entity;

    /////////////////////
    /////////////////////
    // GETTERS SETTERS //
    /////////////////////
    /////////////////////

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return MetadataEntityAttribute
     */
    public function setId(int $id): MetadataEntityAttribute
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return MetadataEntityAttribute
     */
    public function setKey(string $key): MetadataEntityAttribute
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return MetadataEntityAttribute
     */
    public function setValue(string $value): MetadataEntityAttribute
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return MetadataEntity
     */
    public function getEntity(): MetadataEntity
    {
        return $this->entity;
    }

    /**
     * @param MetadataEntity $entity
     * @return MetadataEntityAttribute
     */
    public function setEntity(MetadataEntity $entity): MetadataEntityAttribute
    {
        $this->entity = $entity;
        return $this;
    }

}
