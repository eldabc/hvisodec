<?php

namespace Siwapp\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Siwapp\InvoiceBundle\Entity\Invoice;
use Siwapp\RecurringInvoiceBundle\Entity\RecurringInvoice;
use Siwapp\EstimateBundle\Entity\Estimate;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Customer
 *
 * @ORM\Table(name="customer", uniqueConstraints={@ORM\UniqueConstraint(name="customer_unique", columns={
 *     "name",
 *     "email"
 * })})
 * @ORM\Entity(repositoryClass="Siwapp\CustomerBundle\Repository\CustomerRepository")
 * @UniqueEntity("email")
 */
class Customer implements \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="identification", type="string", length=128, nullable=true)
     */
    private $identification;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=191)
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_person", type="string", length=255, nullable=true)
     */
    private $contactPerson;

    /**
     * @var string
     *
     * @ORM\Column(name="invoicing_address", type="string", length=255, nullable=true)
     */
    private $invoicingAddress;
    
    /**
     * @var string
     *
     * @ORM\Column(name="postal_code", type="string", length=10, nullable=true)
     */
    private $postalCode;
    
    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=255, nullable=true)
     */
    private $location;
    
    /**
     * @var string
     *
     * @ORM\Column(name="province", type="string", length=255, nullable=true)
     */
    private $province;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_address", type="text", nullable=true)
     */
    private $shippingAddress;
    
    /**
     * @var string
     *
     * @ORM\Column(name="mandato", type="string", nullable=true, length=10)
     */
    private $mandato;
    
    /**
     * @var date $fecha_mandato
     *
     * @ORM\Column(name="fecha_mandato", type="date", nullable=true)
     */
    private $fecha_mandato;
    
    /**
     * @var string
     *
     * @ORM\Column(name="bic", type="string", nullable=true, length=11)
     */
    private $bic;
    
    /**
     * @var string
     *
     * @ORM\Column(name="iban", type="string", nullable=true, length=100)
     */
    private $iban;

    /**
     * @ORM\OneToMany(targetEntity="Siwapp\InvoiceBundle\Entity\Invoice", mappedBy="customer")
     */
    private $invoices;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Customer
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set identification
     *
     * @param string $identification
     *
     * @return Customer
     */
    public function setIdentification($identification)
    {
        $this->identification = $identification;

        return $this;
    }

    /**
     * Get identification
     *
     * @return string
     */
    public function getIdentification()
    {
        return $this->identification;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Customer
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set contactPerson
     *
     * @param string $contactPerson
     *
     * @return Customer
     */
    public function setContactPerson($contactPerson)
    {
        $this->contactPerson = $contactPerson;

        return $this;
    }

    /**
     * Get contactPerson
     *
     * @return string
     */
    public function getContactPerson()
    {
        return $this->contactPerson;
    }

    /**
     * Set invoicingAddress
     *
     * @param string $invoicingAddress
     *
     * @return Customer
     */
    public function setInvoicingAddress($invoicingAddress)
    {
        $this->invoicingAddress = $invoicingAddress;

        return $this;
    }

    /**
     * Get invoicingAddress
     *
     * @return string
     */
    public function getInvoicingAddress()
    {
        return $this->invoicingAddress;
    }
    
    /**
     * Set postalCode
     *
     * @param string $postalCode
     *
     * @return Customer
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
        
        return $this;
    }
    
    /**
     * Get postalCode
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }
    
    /**
     * Set location
     *
     * @param string $location
     *
     * @return Customer
     */
    public function setLocation($location)
    {
        $this->location = $location;
        
        return $this;
    }
    
    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }
    
    /**
     * Set province
     *
     * @param string $province
     *
     * @return Customer
     */
    public function setProvince($province)
    {
        $this->province = $province;
        
        return $this;
    }
    
    /**
     * Get province
     *
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * Set shippingAddress
     *
     * @param string $shippingAddress
     *
     * @return Customer
     */
    public function setShippingAddress($shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    /**
     * Get shippingAddress
     *
     * @return string
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }
    
    /**
     * Set mandato
     *
     * @param string $mandato
     *
     * @return Customer
     */
    public function setMandato($mandato)
    {
        $this->mandato = $mandato;
        
        return $this;
    }
    
    /**
     * Get mandato
     *
     * @return string
     */
    public function getMandato()
    {
        return $this->mandato;
    }
    
    /**
     * Set fecha_mandato
     *
     * @param date $fechaMandato
     */
    public function setFechaMandato($fechaMandato)
    {
        $this->fecha_mandato = $fechaMandato instanceof \DateTime ?
        $fechaMandato : new \DateTime($fechaMandato);
    }
    
    /**
     * Get fecha_mandato
     *
     * @return date
     */
    public function getFechaMandato()
    {
        return $this->fecha_mandato;
    }
    
    /**
     * Set bic
     *
     * @param string $bic
     *
     * @return Customer
     */
    public function setBic($bic)
    {
        $this->bic = $bic;
        
        return $this;
    }
    
    /**
     * Get bic
     *
     * @return string
     */
    public function getBic()
    {
        return $this->bic;
    }
    
    /**
     * Set iban
     *
     * @param string $iban
     *
     * @return Customer
     */
    public function setIban($iban)
    {
        $this->iban = $iban;
        
        return $this;
    }
    
    /**
     * Get iban
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    public function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'email'=> $this->getEmail(),
            'identification' => $this->getIdentification(),
            'contact_person' => $this->getContactPerson(),
            'invoicing_address' => $this->getInvoicingAddress(),
            'shipping_address' => $this->getShippingAddress(),
            'postal_code' => $this->getPostalCode(),
            'location' => $this->getLocation(),
            'province' => $this->getProvince(),
            'mandato' => $this->getMandato(),
            'fecha_mandato' => $this->getFechaMandato(),
            'bic' => $this->getBic(),
            'iban' => $this->getIban(),
        );
    }

    public function label()
    {
        return $this->getName();
    }
}
