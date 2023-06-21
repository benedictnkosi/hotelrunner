<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Functionality
 *
 * @ORM\Table(name="functionality")
 * @ORM\Entity
 */
class Functionality
{
    /**
     * @var int
     *
     * @ORM\Column(name="functionality_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $functionalityId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=200, nullable=false)
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=false)
     */
    private $enabled = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="area", type="string", length=50, nullable=false)
     */
    private $area;

    /**
     * @return int
     */
    public function getFunctionalityId(): int
    {
        return $this->functionalityId;
    }

    /**
     * @param int $functionalityId
     */
    public function setFunctionalityId(int $functionalityId): void
    {
        $this->functionalityId = $functionalityId;
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
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool|string
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool|string $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getArea(): string
    {
        return $this->area;
    }

    /**
     * @param string $area
     */
    public function setArea(string $area): void
    {
        $this->area = $area;
    }


}
