<?php

namespace Siwapp\InvoiceBundle\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\Inflector;
use Siwapp\CoreBundle\Entity\AbstractInvoice;
use Symfony\Component\Validator\Constraints as Assert;
use Siwapp\ProductBundle\Entity\Product;

/**
 * Siwapp\InvoiceBundle\Entity\Invoice
 *
 * @ORM\Table(indexes={
 *    @ORM\Index(name="invoice_cstnm_idx", columns={"customer_name"}),
 *    @ORM\Index(name="invoice_cstid_idx", columns={"customer_identification"}),
 *    @ORM\Index(name="invoice_cstml_idx", columns={"customer_email"}),
 *    @ORM\Index(name="invoice_cntct_idx", columns={"contact_person"})
 * })
 * @ORM\Entity(repositoryClass="Siwapp\InvoiceBundle\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks()
 * * @ORM\EntityListeners( {"Siwapp\InvoiceBundle\EventListener\ItemListener"} )
 */
class Invoice extends AbstractInvoice
{
    /**
     * @ORM\ManyToMany(targetEntity="Payment", orphanRemoval=true, cascade={"all"})
     * @ORM\JoinTable(name="invoices_payments",
     *      joinColumns={@ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(
     *          name="payment_id", referencedColumnName="id", unique=true, onDelete="CASCADE"
     *      )}
     * )
     */
    private $payments;

    /**
     * @var boolean $sent_by_email
     *
     * @ORM\Column(name="sent_by_email", type="boolean", nullable=true)
     */
    private $sent_by_email;
    
    /**
     * @var boolean $domicile
     *
     * @ORM\Column(name="domicile", type="boolean", nullable=true)
     */
    private $domicile;

    /**
     * @var integer $number
     *
     * @ORM\Column(name="number", type="integer", nullable=true)
     */
    private $number;

    /**
     * @var date $issue_date
     *
     * @ORM\Column(name="issue_date", type="date", nullable=true)
     * @Assert\Date()
     */
    private $issue_date;

    /**
     * @var date $due_date
     *
     * @ORM\Column(name="due_date", type="date", nullable=true)
     * @Assert\Date()
     */
    private $due_date;
    
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
     * @ORM\ManyToMany(targetEntity="Siwapp\CoreBundle\Entity\Item", cascade={"all"}, inversedBy="invoice")
     * @ORM\JoinTable(name="invoices_items",
     *      joinColumns={@ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
     * )
     * @Assert\NotBlank()
     */
    protected $items;

    /**
     * @var boolean $closed
     *
     * @ORM\Column(name="closed", type="boolean", nullable=true)
     */
    private $forcefully_closed;

    public function __construct()
    {
        parent::__construct();
        $this->payments = new ArrayCollection();
        $this->issue_date = new \DateTime();
        $this->due_date = new \DateTime();
    }

    /**
     * @return boolean
     */
    public function isClosed()
    {
        return $this->status === Invoice::CLOSED;
    }

    /**
     * @return boolean
     */
    public function isOpen()
    {
        return in_array($this->status, [Invoice::OPENED, Invoice::OVERDUE], true);
    }

    /**
     * @return boolean
     */
    public function isOverdue()
    {
        return $this->status === Invoice::OVERDUE;
    }

    /**
     * @return boolean
     */
    public function isDraft()
    {
        return $this->status === Invoice::DRAFT;
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
     * Set domicile
     *
     * @param boolean $domicile
     */
    public function setDomicile($domicile)
    {
        $this->domicile = $domicile;
    }
    
    /**
     * Get domicile
     *
     * @return boolean
     */
    public function isDomicile(): bool
    {
        return (bool) $this->domicile;
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
     * Set due_date
     *
     * @param date $dueDate
     */
    public function setDueDate($dueDate)
    {
        $this->due_date = $dueDate instanceof \DateTime ?
        $dueDate : new \DateTime($dueDate);
    }

    /**
     * Get due_date
     *
     * @return date
     */
    public function getDueDate()
    {
        return $this->due_date;
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

    /**
     * Add payments
     *
     * @param Siwapp\InvoiceBundle\Entity\Payment $payment
     */
    public function addPayment(\Siwapp\InvoiceBundle\Entity\Payment $payment)
    {
        $this->payments[] = $payment;
    }

    /**
     * Removes a payment.
     *
     * @param Siwapp\InvoiceBundle\Entity\Payment $payment
     */
    public function removePayment(\Siwapp\InvoiceBundle\Entity\Payment $payment)
    {
        foreach ($this->getPayments() as $key => $value) {
            if ($value === $payment) {
                unset($this->payments[$key]);
                break;
            }
        }
    }

    /**
     * Get payments
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getPayments()
    {
        return $this->payments;
    }

    public function setForcefullyClosed($value)
    {
        $this->forcefully_closed = $value;
    }

    public function isForcefullyClosed()
    {
        return $this->forcefully_closed;
    }

    /** **************** CUSTOM METHODS AND PROPERTIES **************  */

    /**
     * TODO: provide the serie .
     */
    public function __toString()
    {
        return $this->label();
    }

    public function label(string $draftLabel = '[draft]')
    {
        $series = $this->getSeries();
        $label = '';
        $label .= $series ? $series->getValue() : '';
        $label .= $this->isDraft() ? $draftLabel : $this->getNumber();

        return $label;
    }

    const DRAFT    = 0;
    const CLOSED   = 1;
    const OPENED   = 2;
    const OVERDUE  = 3;

    public function getDueAmount()
    {
        if ($this->isDraft()) {
            return null;
        }
        return $this->getGrossAmount() - $this->getPaidAmount();
    }

    /**
     * try to catch custom methods to be used in twig templates
     */
    public function __get($name)
    {
        if (strpos($name, 'tax_amount_') === 0) {
            return $this->calculate($name, true);
        }
        $method = Inflector::camelize("get_{$name}");
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return false;
    }

    public function __isset($name)
    {
        if (strpos($name, 'tax_amount_') === 0) {
            return true;
        }

        if (in_array($name, ['due_amount'])) {
            return true;
        }

        if (in_array($name, array_keys(get_object_vars($this)))) {
            return true;
        }

        return parent::__isset($name);
    }

    public function getStatusString()
    {
        switch ($this->status) {
            case Invoice::DRAFT;
                $status = 'draft';
             break;
            case Invoice::CLOSED;
                $status = 'closed';
            break;
            case Invoice::OPENED;
                $status = 'opened';
            break;
            case Invoice::OVERDUE:
                $status = 'overdue';
                break;
            default:
                $status = 'unknown';
                break;
        }
        return $status;
    }

    /**
     * checkStatus
     * checks and sets the status
     *
     * @return Siwapp\InvoiceBundle\Invoice $this
     */
    public function checkStatus()
    {
        if ($this->status == Invoice::DRAFT) {
            return $this;
        }
        if ($this->isForcefullyClosed() || $this->getDueAmount() == 0) {
            $this->setStatus(Invoice::CLOSED);
        } else {
            if ($this->getDueDate()->getTimestamp() > strtotime(date('Y-m-d'))) {
                $this->setStatus(Invoice::OPENED);
            } else {
                $this->setStatus(Invoice::OVERDUE);
            }
        }

        return $this;
    }

    public function checkAmounts()
    {
        parent::checkAmounts();
        $this->setPaidAmount($this->calculate('paid_amount'));

        return $this;
    }

    public function checkNumber(LifecycleEventArgs $args)
    {
        // compute the number of invoice
        if ((!$this->number && $this->status!=self::DRAFT) ||
            ($args instanceof PreUpdateEventArgs && $args->hasChangedField('series') && $this->status!=self::DRAFT)
        ) {
            $repo = $args->getEntityManager()->getRepository('SiwappInvoiceBundle:Invoice');
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
    
    /**
     * @ORM\PrePersist()
     */
    public function delStock(LifecycleEventArgs $args)
    {
        if ($this->status != Invoice::DRAFT){
            foreach ($this->getItems() as $item) {
                if (!$item->getProduct() instanceof Product){
                    continue;
                }
                
                $product = $item->getProduct();
                if ($product->getStock() < $item->getQuantity()){
                    return false;
                }else{
                    $product->setStock($product->getStock() - $item->getQuantity());
                }
                
            }
        }
    }
}
