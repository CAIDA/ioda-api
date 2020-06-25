<?php

namespace App\Entity\Alerts;

use App\Entity\Alerts\MetadataEntityAttribute;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EntitiesRepository")
 * @ORM\Table(name="mddb_entity")
 */
class MetadataEntity
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
    private $code;

    /**
     * @Groups({"public"})
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @Groups({"public"})
     * @ORM\ManyToOne(targetEntity="MetadataEntityType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * @var MetadataEntityType
     */
    private $type;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\ManyToMany(targetEntity="MetadataEntity")
     * @ORM\JoinTable(name="mddb_entity_relationship",
     *     joinColumns={@ORM\JoinColumn(name="from_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="to_id", referencedColumnName="id")}
     *   )
     */
    private $relationships;

    /**
     * @Groups({"public"})
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(targetEntity="MetadataEntityAttribute", mappedBy="entity")
     */
    private $attributes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $attrs;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->relationships = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attributes = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * @return MetadataEntity
     */
    public function setId(int $id): MetadataEntity
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return MetadataEntity
     */
    public function setCode(string $code): MetadataEntity
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return MetadataEntity
     */
    public function setName(string $name): MetadataEntity
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return MetadataEntityType
     */
    public function getType(): MetadataEntityType
    {
        return $this->type;
    }

    /**
     * @param MetadataEntityType $type
     * @return MetadataEntity
     */
    public function setType(MetadataEntityType $type): MetadataEntity
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Add relationships
     *
     * @param MetadataEntity $metadata
     * @return MetadataEntity
     */
    public function addRelationship(MetadataEntity $metadata)
    {
        if (!$this->relationships->contains($metadata)) {
            $this->relationships[] = $metadata;
            $metadata->addRelationship($this);
        }

        return $this;
    }

    /**
     * Remove relationships
     *
     * @param MetadataEntity $metadata
     */
    public function removeRelationship(MetadataEntity $metadata)
    {
        if ($this->relationships->contains($metadata)) {
            $this->relationships->removeElement($metadata);
            $metadata->removeRelationship($this);
        }
    }

    /**
     * Get relationships
     *
     * @return array
     */
    public function getRelationships()
    {
        return $this->relationships->getValues();
    }

    /**
     * Get attribute
     *
     * @return string
     */
    public function getAttribute($key)
    {
        $this->initAttrs();
        return $this->attrs[$key];
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public
    function getAttributes()
    {
        return $this->attributes->getValues();
    }

    /**
     * Add a new attribute to this entity.
     * Cannot modify a previously added attribute.
     *
     * @param string $key
     * @param string $value
     *
     * @return MetadataEntity
     */
    public function addAttribute($key, $value)
    {
        $this->initAttrs();
        if (array_key_exists($key, $this->attrs)) {
            throw new \RuntimeException("Key '$key' already exists");
        }

        $this->attrs[$key] = $value;
        $attr = (new MetadataEntityAttribute())
            ->setKey($key)
            ->setValue($value)
            ->setMetadata($this);
        $this->attributes->add($attr);

        return $this;
    }

    private function initAttrs()
    {
        if (!isset($this->attrs)) {
            $this->attrs = [];
            foreach ($this->getAttributes() as $attribute) {
                $this->attrs[$attribute->getKey()] = $attribute->getValue();
            }
            return true;
        }
        return false;
    }
}
