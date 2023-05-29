<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Properties
 *
 * @ORM\Table(name="properties")
 * @ORM\Entity
 */
class Properties
{
    /**
     * @var int
     *
     * @ORM\Column(name="idProperties", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idproperties;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=45, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="address", type="string", length=45, nullable=true)
     */
    private $address;

    /**
     * @var int|null
     *
     * @ORM\Column(name="late_fee", type="integer", nullable=true)
     */
    private $lateFee = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="quickbooks_token", type="string", length=100, nullable=true)
     */
    private $quickbooksToken;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rent_due", type="integer", nullable=true, options={"default"="1"})
     */
    private $rentDue = 1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rent_late_days", type="integer", nullable=true, options={"default"="7"})
     */
    private $rentLateDays = 7;

    /**
     * @var string|null
     *
     * @ORM\Column(name="type", type="string", length=45, nullable=true)
     */
    private $type;


}
