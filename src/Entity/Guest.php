<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Guest
 *
 * @ORM\Table(name="guest", indexes={@ORM\Index(name="fk_guest_property", columns={"property"})})
 * @ORM\Entity
 */
class Guest
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
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=45, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="id_image", type="string", length=45, nullable=true, options={"default"="Not Verified"})
     */
    private $idImage = 'Not Verified';

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone_number", type="string", length=45, nullable=true)
     */
    private $phoneNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=45, nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="state", type="string", length=45, nullable=true, options={"default"="Active"})
     */
    private $state = 'Active';

    /**
     * @var string|null
     *
     * @ORM\Column(name="comments", type="string", length=45, nullable=true)
     */
    private $comments;

    /**
     * @var string|null
     *
     * @ORM\Column(name="id_number", type="string", length=20, nullable=true)
     */
    private $idNumber;

    /**
     * @var bool
     *
     * @ORM\Column(name="rewards", type="boolean", nullable=false)
     */
    private $rewards = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="gender", type="string", length=6, nullable=true)
     */
    private $gender;

    /**
     * @var int
     *
     * @ORM\Column(name="citizenship", type="integer", nullable=false)
     */
    private $citizenship = '0';

    /**
     * @var Property
     *
     * @ORM\ManyToOne(targetEntity="Property")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="property", referencedColumnName="id")
     * })
     */
    private $property;

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
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getIdImage(): ?string
    {
        return $this->idImage;
    }

    /**
     * @param string|null $idImage
     */
    public function setIdImage(?string $idImage): void
    {
        $this->idImage = $idImage;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     */
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string|null $state
     */
    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    /**
     * @param string|null $comments
     */
    public function setComments(?string $comments): void
    {
        $this->comments = $comments;
    }

    /**
     * @return string|null
     */
    public function getIdNumber(): ?string
    {
        return $this->idNumber;
    }

    /**
     * @param string|null $idNumber
     */
    public function setIdNumber(?string $idNumber): void
    {
        $this->idNumber = $idNumber;
    }

    /**
     * @return bool
     */
    public function isRewards(): bool|string
    {
        return $this->rewards;
    }

    /**
     * @param bool $rewards
     */
    public function setRewards(bool|string $rewards): void
    {
        $this->rewards = $rewards;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @param string|null $gender
     */
    public function setGender(?string $gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @return int
     */
    public function getCitizenship(): int|string
    {
        return $this->citizenship;
    }

    /**
     * @param int $citizenship
     */
    public function setCitizenship(int|string $citizenship): void
    {
        $this->citizenship = $citizenship;
    }

    /**
     * @return Property
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @param Property $property
     */
    public function setProperty(Property $property): void
    {
        $this->property = $property;
    }


}
