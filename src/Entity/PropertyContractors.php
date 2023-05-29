<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PropertyContractors
 *
 * @ORM\Table(name="property_contractors", indexes={@ORM\Index(name="property_contractors_property_idx", columns={"property"}), @ORM\Index(name="property_contractors_contractor_idx", columns={"contractor"})})
 * @ORM\Entity
 */
class PropertyContractors
{
    /**
     * @var int
     *
     * @ORM\Column(name="idproperty_contractors", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idpropertyContractors;

    /**
     * @var \Contractors
     *
     * @ORM\ManyToOne(targetEntity="Contractors")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contractor", referencedColumnName="idcontractors")
     * })
     */
    private $contractor;

    /**
     * @var \Properties
     *
     * @ORM\ManyToOne(targetEntity="Properties")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="property", referencedColumnName="idProperties")
     * })
     */
    private $property;


}
