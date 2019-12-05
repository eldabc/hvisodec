<?php
/**
 * Created by PhpStorm.
 * User: jvasquez
 * Date: 13/1/2017
 * Time: 5:06 PM
 */

namespace Siwapp\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Siwapp\CoreBundle\Entity\AbstractInvoice;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Siwapp\OrderBundle\Entity\Order
 *
 * @ORM\Table(name="`order`", indexes={
 *    @ORM\Index(name="order_cstnm_idx", columns={"customer_name"}),
 *    @ORM\Index(name="order_cstid_idx", columns={"customer_identification"}),
 *    @ORM\Index(name="order_cstml_idx", columns={"customer_email"}),
 *    @ORM\Index(name="order_cntct_idx", columns={"contact_person"})
 * })
 * @ORM\Entity(repositoryClass="Siwapp\OrderBundle\Repository\OrderRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Order extends AbstractInvoice
{
    /**
     * @ORM\ManyToMany(targetEntity="Siwapp\CoreBundle\Entity\Item", cascade={"all"}, inversedBy="order")
     * @ORM\JoinTable(name="orders_items",
     *      joinColumns={@ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
     * )
     * @Assert\NotBlank()
     */
    protected $items;

    /**
     * @var integer $number
     *
     * @ORM\Column(name="number", type="integer", nullable=true)
     */
    private $number;

    /**
     * @var boolean $imported
     *
     * @ORM\Column(type="boolean")
     */
    private $imported = false;

    /**
     * @var boolean $sent_by_email
     *
     * @ORM\Column(name="sent_by_email", type="boolean", nullable=true)
     */
    private $sent_by_email;

    /**
     * @var date $issue_date
     *
     * @ORM\Column(name="issue_date", type="date", nullable=true)
     * @Assert\Date()
     */
    private $issue_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="estimate_to", type="bigint", nullable=true)
     */
    private $estimate_to;
    
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
     * @ORM\Column(name="mandato", type="string", nullable=true, length=10)
     */
    private $mandato;
    
    /**
     * @var date $fecha_mandato
     *
     * @ORM\Column(name="fecha_mandato", type="date", nullable=true)
     * @Assert\Date()
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

    public function __construct()
    {
        parent::__construct();
        $this->issue_date = new \DateTime();
    }

    /**
     * Set draft
     *
     * @param boolean $draft
     */
    public function setDraft($draft)
    {
        $this->draft = $draft;
    }

    /**
     * Get draft
     *
     * @return boolean
     */
    public function isDraft()
    {
        return $this->status == self::DRAFT;
    }

    /**
     * @return boolean
     */
    public function isPending()
    {
        return $this->status == self::PENDING;
    }

    /**
     * @return boolean
     */
    public function isApproved()
    {
        return $this->status == self::APPROVED;
    }

    /**
     * Set number
     *
     * @param integer $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get number
     *
     * @return integer
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set sent_by_email
     *
     * @param boolean $sentByEmail
     */
    public function setSentByEmail($sentByEmail)
    {
        $this->sent_by_email = $sentByEmail;
    }

    /**
     * Get sent_by_email
     *
     * @return boolean
     */
    public function isSentByEmail(): bool
    {
        return (bool) $this->sent_by_email;
    }

    /**
     * Set issue_date
     *
     * @param date $issueDate
     */
    public function setIssueDate($issueDate)
    {
        $this->issue_date = $issueDate instanceof \DateTime ?
            $issueDate: new \DateTime($issueDate);
    }

    /**
     * Get issue_date
     *
     * @return date
     */
    public function getIssueDate()
    {
        return $this->issue_date;
    }

    /**
     * @return bool
     */
    public function isImported(): bool
    {
        return $this->imported;
    }

    /**
     * @param bool $imported
     */
    public function setImported(bool $imported)
    {
        $this->imported = $imported;
    }

    /**
     * @return int
     */
    public function getEstimateTo(): int
    {
        return $this->estimate_to;
    }

    /**
     * @param int $estimate_to
     */
    public function setEstimateTo(int $estimate_to)
    {
        $this->estimate_to = $estimate_to;
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

    /** ********** CUSTOM METHODS AND PROPERTIES ************* */


    /**
     * try to catch custom methods to be used in twig templates
     */
    public function __get($name)
    {
        $method = Inflector::camelize("get_{$name}");
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return false;
    }

    public function __isset($name)
    {
        if (in_array($name, array_keys(get_object_vars($this)))) {
            return true;
        }

        return parent::__isset($name);
    }

    public function __toString()
    {
        return $this->label();
    }

    public function label()
    {
        /*$series = $this->getSeries();
        $label = '';
        $label .= $series ? $series->getValue() : '';
        $label .= $this->isDraft() ? '[draft]' : $this->getNumber();*/
        $series = $this->getSeries();
        $label = '';
        $label .= $series ? $series->getValue() : '';
        //$label .= $this->isDraft() ? '[draft]' : $this->getNumber();
        $label .= !$this->getNumber() ? '[draft]' : $this->getNumber();

        return $label;
    }

    const DRAFT    = 0;
    const PENDING  = 1;
    const APPROVED = 2;
    const REJECTED = 3;

    public function getStatusString()
    {
        switch ($this->status) {
            case self::DRAFT;
                $status = 'draft';
                break;
            case self::REJECTED;
                $status = 'rejected';
                break;
            case self::APPROVED;
                $status = 'approved';
                break;
            case self::PENDING:
                $status = 'pending';
                break;
            default:
                $status = 'unknown';
                break;
        }
        return $status;
    }

    public function checkStatus()
    {
        if ($this->isDraft()) {
            $this->setStatus(Order::DRAFT);
        }
    }

    public function checkNumber($args)
    {
        // compute the number of invoice
        //if ( $this->status != self::DRAFT && ( !$this->number || ( $args instanceof PreUpdateEventArgs && $args->hasChangedField('series') ) ) ) {
        if ( /*$this->status != self::DRAFT &&*/ !$this->number || ( $args instanceof PreUpdateEventArgs && $args->hasChangedField('series') && $this->status!=self::DRAFT) ) {
            $repo = $args->getEntityManager()->getRepository('SiwappOrderBundle:Order');
            $series = $this->getSeries();
            if ($repo && $series) {
                $this->setNumber($repo->getNextNumber($series));
            }
        }
    }

    /* ********** LIFECYCLE CALLBACKS *********** */

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preSave(LifecycleEventArgs $args)
    {
        $this->checkAmounts();
        parent::presave($args);
        $this->checkNumber($args);
    }
}