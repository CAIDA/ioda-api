<?php

namespace App\Entity\Ioda;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SymUrlRepository")
 * @ORM\Table(indexes={@ORM\Index(name="long_url_idx", columns={"long_url"})})
 */
class SymUrl
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups({"public"})
     */
    private $shortTag;

    /**
     * @ORM\Column(type="text")
     * @Groups({"public"})
     */
    private $longUrl;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"public"})
     */
    private $useCount;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"public"})
     */
    private $dateCreated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"public"})
     */
    private $dateLastUsed;

    public function __construct()
    {
        $this->setDateCreated(new \DateTime());
        $this->setDateLastUsed(null);
        $this->setUseCount(0);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShortTag(): ?string
    {
        return $this->shortTag;
    }

    public function setShortTag(string $shortTag): self
    {
        $this->shortTag = $shortTag;

        return $this;
    }

    public function getLongUrl(): ?string
    {
        return $this->longUrl;
    }

    public function setLongUrl(string $longUrl): self
    {
        $this->longUrl = $longUrl;

        return $this;
    }

    public function getUseCount(): ?int
    {
        return $this->useCount;
    }

    public function setUseCount(int $useCount): self
    {
        $this->useCount = $useCount;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateLastUsed(): ?\DateTimeInterface
    {
        return $this->dateLastUsed;
    }

    public function setDateLastUsed(?\DateTimeInterface $dateLastUsed): self
    {
        $this->dateLastUsed = $dateLastUsed;

        return $this;
    }
}
