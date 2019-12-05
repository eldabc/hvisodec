<?php

namespace Siwapp\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Siwapp\CoreBundle\Entity\Serie
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class Series
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string $value
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @var integer $first_number
     *
     * @ORM\Column(name="first_number", type="integer")
     */
    private $first_number;

    /**
     * @var boolean $enabled
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;
    
    /**
     * @var boolean $default_invoice
     *
     * @ORM\Column(name="default_invoice", type="boolean")
     */
    private $default_invoice = 0;
    
    /**
     * @var boolean $default_payment
     *
     * @ORM\Column(name="default_payment", type="boolean")
     */
    private $default_payment = 0;

    public function __construct()
    {
        $this->first_number = 1;
        $this->enabled = 1;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * Set value
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set first_number
     *
     * @param integer $firstNumber
     */
    public function setFirstNumber($firstNumber)
    {
        $this->first_number = $firstNumber;
    }

    /**
     * Get first_number
     *
     * @return integer
     */
    public function getFirstNumber()
    {
        return $this->first_number;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
    
    /**
     * Get default_invoice
     *
     * @return boolean
     */
    public function getDefaultInvoice()
    {
    	return $this->default_invoice;
    }
    
    /**
     * @param boolean $default_invoice
     */
    public function setDefaultInvoice($default_invoice)
    {
    	$this->default_invoice = $default_invoice;
    }
    
    /**
     * Get default_payment
     *
     * @return boolean
     */
    public function getDefaultPayment()
    {
    	return $this->default_payment;
    }
    
    /**
     * @param boolean $default_payment
     */
    public function setDefaultPayment($default_payment)
    {
    	$this->default_payment = $default_payment;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
