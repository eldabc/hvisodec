<?php

namespace Siwapp\ProviderBundle\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\Validator\Constraints as Assert;
use Siwapp\CoreBundle\Entity\AbstractInvoice;
use Siwapp\ProductBundle\Entity\Product;


/**
 * Siwapp\ProviderBundle\Entity\InvoiceProvider
 *
 * @ORM\Entity(repositoryClass="Siwapp\ProviderBundle\Repository\InvoiceProviderRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class InvoiceProvider extends AbstractInvoice
{
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
     * @ORM\Column(name="invoice", type="string", nullable=true)
     *
     * @Assert\File(
     * 		mimeTypes={ "application/pdf", "application/x-pdf", "image/jpeg" },
     *	    mimeTypesMessage = "Por favor cargue un archivo válido."
     * )
     */
    private $invoice;
    
    /**
     * @ORM\ManyToOne(targetEntity="Siwapp\ProviderBundle\Entity\Provider", inversedBy="invoicesProvider")
     * @ORM\JoinColumn(name="provider_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $provider;
    
    /**
     * @ORM\ManyToMany(targetEntity="Credit", orphanRemoval=true, cascade={"all"})
     * @ORM\JoinTable(name="invoice_credits",
     *      joinColumns={@ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(
     *          name="credit_id", referencedColumnName="id", unique=true, onDelete="CASCADE"
     *      )}
     * )
     */
    private $credits;
    
    /**
     * @var string $provider_name
     *
     * @ORM\Column(name="provider_name", type="string", length=255, nullable=true)
     */
    private $provider_name;
    
    /**
     * @var string $provider_identification
     *
     * @ORM\Column(name="provider_identification", type="string", length=128, nullable=true)
     */
    private $provider_identification;
    
    /**
     * @var smallint $status
     *
     * @ORM\Column(name="status", type="smallint", nullable=true)
     * @Assert\Length(min=0, max=3)
     */
    protected $status = 0;

    /**
     * @ORM\ManyToMany(targetEntity="Siwapp\CoreBundle\Entity\Item", cascade={"all"}, inversedBy="invoiceProvider")
     * @ORM\JoinTable(name="invopro_items",
     *      joinColumns={@ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
     * )
     * @Assert\NotBlank()
     */
    protected $items;


    public function __construct() {
        parent::__construct();
    	$this->provider = new ArrayCollection();
    	$this->credits = new ArrayCollection();
    	$this->issue_date = new \DateTime();
    	$this->due_date = new \DateTime();
    	$this->items = new ArrayCollection();
    }
    
    /**
     * @return boolean
     */
    public function isClosed()
    {
        return $this->status === InvoiceProvider::CLOSED;
    }
    
    /**
     * @return boolean
     */
    public function isOpen()
    {
        return in_array($this->status, [InvoiceProvider::OPENED, InvoiceProvider::OVERDUE], true);
    }
    
    /**
     * @return boolean
     */
    public function isOverdue()
    {
        return $this->status === InvoiceProvider::OVERDUE;
    }
    
    /**
     * @return boolean
     */
    public function isDraft()
    {
        return $this->status === InvoiceProvider::DRAFT;
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
     * Set invoice
     *
     * @param string $invoice
     *
     * @return InvoiceProvider
     */
    public function setInvoice($invoice)
    {
    	$this->invoice = $invoice;
    	
    	return $this;
    }
    
    /**
     * Get invoice
     *
     * @return string
     */
    public function getInvoice()
    {
    	return $this->invoice;
    }
    
    /**
     * Set provider.
     *
     * @param Provider $provider
     */
    public function setProvider(Provider $provider)
    {
    	$this->provider = $provider;
    }
    
    /**
     * Add credits
     *
     * @param Siwapp\ProviderBundle\Entity\Credit $credit
     */
    public function addCredit(\Siwapp\ProviderBundle\Entity\Credit $credit)
    {
        $this->credits[] = $credit;
    }
    
    /**
     * Removes a credit.
     *
     * @param Siwapp\ProviderBundle\Entity\Credit $credit
     */
    public function removeCredit(\Siwapp\ProviderBundle\Entity\Credit $credit)
    {
        foreach ($this->getCredits() as $key => $value) {
            if ($value === $credit) {
                unset($this->credits[$key]);
                break;
            }
        }
    }
    
    /**
     * Get credits
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getCredits()
    {
        return $this->credits;
    }
    
    /**
     * Set provider_name
     *
     * @param string $providerName
     */
    public function setProviderName($providerName)
    {
    	$this->provider_name = $providerName;
    }
    
    /**
     * Get provider_name
     *
     * @return string
     */
    public function getProviderName()
    {
    	return $this->provider_name;
    }
    
    /**
     * Set provider_identification
     *
     * @param string $providerIdentification
     */
    public function setProviderIdentification($providerIdentification)
    {
    	$this->provider_identification = $providerIdentification;
    }
    
    /**
     * Get provider_identification
     *
     * @return string
     */
    public function getProviderIdentification()
    {
    	return $this->provider_identification;
    }
    
    /**
     * Set status
     *
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
    
    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /** **************** CUSTOM METHODS AND PROPERTIES **************  */
    
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
    
    
    const DRAFT    = 0;
    const CLOSED   = 1;
    const OPENED   = 2;
    const OVERDUE  = 3;
    
    public function getStatusString()
    {
        switch ($this->status) {
            case InvoiceProvider::DRAFT;
            $status = 'draft';
            break;
            case InvoiceProvider::CLOSED;
            $status = 'closed';
            break;
            case InvoiceProvider::OPENED;
            $status = 'opened';
            break;
            case InvoiceProvider::OVERDUE:
                $status = 'overdue';
                break;
            default:
                $status = 'unknown';
                break;
        }
        return $status;
    }
    
    /**
     * calculate values over items
     *
     * Warning!! this method only works when called from a real entity, not
     * the abstract.
     *
     * @param string $field
     * @param boolean $rounded
     * @return float
     */
    public function calculate($field, $rounded = false)
    {
        $val = 0;
        switch ($field) {
            case 'paid_amount':
                foreach ($this->getCredits() as $credit) {
                    $val += $credit->getAmount();
                }
                break;
            default:
                foreach ($this->getItems() as $item) {
                    $method = Inflector::camelize('get_'.$field);
                    $val += $item->$method();
                }
                break;
        }
        
        if ($rounded) {
            return round($val, $this->getDecimals());
        }
        
        return $val;
    }

    public function isForcefullyClosed()
    {
        return $this->forcefully_closed;
    }
    
    public function checkAmounts()
    {
        parent::checkAmounts();
        $this->setPaidAmount($this->calculate('paid_amount'));
        
        return $this;
    }
    
    /**
     * checkStatus
     * checks and sets the status
     *
     * @return Siwapp\ProviderBundle\InvoiceProvider $this
     */
    public function checkStatus()
    {
        if ($this->status == 1 || ($this->getGrossAmount() - $this->getPaidAmount()) == 0) {
            $this->setStatus(InvoiceProvider::CLOSED);
        } else {
            if ($this->getDueDate()->getTimestamp() > strtotime(date('Y-m-d'))) {
                $this->setStatus(InvoiceProvider::OPENED);
            } else {
                $this->setStatus(InvoiceProvider::OVERDUE);
            }
        }
        
        return $this;
    }
    
    
    
    /* ********** LIFECYCLE CALLBACKS *********** */
    
    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preSave(LifecycleEventArgs $args)
    {
        $this->checkAmounts();
        $this->checkStatus();
    }
    
    /**
     * @ORM\PrePersist()
     */
    public function addStock(LifecycleEventArgs $args)
    {
        $repo = $args->getEntityManager()->getRepository('SiwappProductBundle:Product');
        
        foreach ($this->getItems() as $item) {
            if (!$item->getProduct() instanceof Product){
                continue;
            }
            
            $product = $repo->findOneBy(array('id' => $item->getProduct()->getId()));
            
            $product->setStock($product->getStock() + $item->getQuantity());
        }
    }
    
}