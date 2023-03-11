<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FlipabilityProperty
 *
 * @ORM\Table(name="flipability_property")
 * @ORM\Entity
 */
class FlipabilityProperty
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="bedrooms", type="integer", nullable=true)
     */
    private $bedrooms;

    /**
     * @var string|null
     *
     * @ORM\Column(name="url", type="text", length=65535, nullable=true)
     */
    private $url;

    /**
     * @var int|null
     *
     * @ORM\Column(name="price", type="integer", nullable=true)
     */
    private $price;

    /**
     * @var string|null
     *
     * @ORM\Column(name="location", type="text", length=65535, nullable=true)
     */
    private $location;

    /**
     * @var float|null
     *
     * @ORM\Column(name="bathrooms", type="float", precision=10, scale=0, nullable=true)
     */
    private $bathrooms;

    /**
     * @var float|null
     *
     * @ORM\Column(name="garage", type="float", precision=10, scale=0, nullable=true)
     */
    private $garage;

    /**
     * @var int|null
     *
     * @ORM\Column(name="erf", type="bigint", nullable=true)
     */
    private $erf;

    /**
     * @var string|null
     *
     * @ORM\Column(name="type", type="text", length=65535, nullable=true)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $timestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=20, nullable=false, options={"default"="new"})
     */
    private $state = 'new';

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getBedrooms(): ?int
    {
        return $this->bedrooms;
    }

    /**
     * @param int|null $bedrooms
     */
    public function setBedrooms(?int $bedrooms): void
    {
        $this->bedrooms = $bedrooms;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return int|null
     */
    public function getPrice(): ?int
    {
        return $this->price;
    }

    /**
     * @param int|null $price
     */
    public function setPrice(?int $price): void
    {
        $this->price = $price;
    }

    /**
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @param string|null $location
     */
    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return float|null
     */
    public function getBathrooms(): ?float
    {
        return $this->bathrooms;
    }

    /**
     * @param float|null $bathrooms
     */
    public function setBathrooms(?float $bathrooms): void
    {
        $this->bathrooms = $bathrooms;
    }

    /**
     * @return float|null
     */
    public function getGarage(): ?float
    {
        return $this->garage;
    }

    /**
     * @param float|null $garage
     */
    public function setGarage(?float $garage): void
    {
        $this->garage = $garage;
    }

    /**
     * @return int|null
     */
    public function getErf(): ?int
    {
        return $this->erf;
    }

    /**
     * @param int|null $erf
     */
    public function setErf(?int $erf): void
    {
        $this->erf = $erf;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime|string
    {
        return $this->timestamp;
    }

    /**
     * @param \DateTime $timestamp
     */
    public function setTimestamp(\DateTime|string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }


}
