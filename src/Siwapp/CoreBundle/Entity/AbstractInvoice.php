<?php

namespace Siwapp\CoreBundle\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\Inflector;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Timestampable\Timestampable;
use Symfony\Component\Validator\Constraints as Assert;
use Siwapp\CoreBundle\Entity\Item;
use Siwapp\ProductBundle\Entity\Product;
use Siwapp\CoreBundle\Entity\Series;
use Siwapp\CustomerBundle\Entity\Customer;
use Siwapp\InvoiceBundle\Entity\Invoice;
use Siwapp\ProviderBundle\Entity\InvoiceProvider;

/**
 * Siwapp\CoreBundle\Entity\AbstractInvoice
 *
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class AbstractInvoice implements Timestampable
{
    use TimestampableEntity;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Siwapp\CustomerBundle\Entity\Customer", inversedBy="invoices")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     */
    protected $customer;

    /**
     * @var string $customer_name
     *
     * @ORM\Column(name="customer_name", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $customer_name;

    /**
     * @var string $customer_identification
     *
     * @ORM\Column(name="customer_identification", type="string", length=128, nullable=true)
     */
    private $customer_identification;

    /**
     * @var string $customer_email
     *
     * @ORM\Column(name="customer_email", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $customer_email;

    /**
     * @var text $invoicing_address
     *
     * @ORM\Column(name="invoicing_address", type="text", nullable=true)
     */
    private $invoicing_address;

    /**
     * @var text $shipping_address
     *
     * @ORM\Column(name="shipping_address", type="text", nullable=true)
     */
    private $shipping_address;

    /**
     * @var string $contact_person
     *
     * @ORM\Column(name="contact_person", type="string", length=255, nullable=true)
     */
    private $contact_person;

    /**
     * @var text $terms
     *
     * @ORM\Column(name="terms", type="text", nullable=true)
     */
    private $terms;

    /**
     * @var text $notes
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;

    /**
     * @var decimal $base_amount
     *
     * @ORM\Column(name="base_amount", type="decimal", scale=3, precision=15, nullable=true)
     */
    private $base_amount;

    /**
     * @var decimal $discount_amount
     *
     * @ORM\Column(name="discount_amount", type="decimal", scale=3, precision=15, nullable=true)
     */
    private $discount_amount;

    /**
     * @var decimal $net_amount
     *
     * @ORM\Column(name="net_amount", type="decimal", scale=3, precision=15, nullable=true)
     */
    private $net_amount;

    /**
     * @var decimal $gross_amount
     *
     * @ORM\Column(name="gross_amount", type="decimal", scale=3, precision=15, nullable=true)
     */
    private $gross_amount;

    /**
     * @var decimal $paid_amount
     *
     * @ORM\Column(name="paid_amount", type="decimal", scale=3, precision=15, nullable=true)
     */
    private $paid_amount;

    /**
     * @var decimal $tax_amount
     *
     * @ORM\Column(name="tax_amount", type="decimal", scale=3, precision=15, nullable=true)
     */
    private $tax_amount;

    /**
     * @var smallint $status
     *
     * @ORM\Column(name="status", type="smallint", nullable=true)
     * @Assert\Length(min=0, max=3)
     */
    protected $status = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Siwapp\CoreBundle\Entity\Series")
     * @ORM\JoinColumn(name="series_id", referencedColumnName="id")
     * @Assert\NotBlank()
     *
     * unidirectional many-to-one
     */
    private $series;

    public function __construct()
    {
        $this->items = new ArrayCollection();
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
     * Set customer parameters from customer entity.
     *
     * @param Siwapp\CustomerBundle\Entity\Customer $customer
     */
    public function setFromCustomer(Customer $customer)
    {
        if (empty($this->customer_name) && $customer->getName()) {
            $this->customer_name = $customer->getName();
        }
        if (empty($this->customer_email) && $customer->getEmail()) {
            $this->customer_email = $customer->getEmail();
        }
        if (empty($this->customer_identification) && $customer->getIdentification()) {
            $this->customer_identification = $customer->getIdentification();
        }
        if (empty($this->contact_person) && $customer->getContactPerson()) {
            $this->contact_person = $customer->getContactPerson();
        }
        if (empty($this->invoicing_address) && $customer->getInvoicingAddress()) {
            $this->invoicing_address = $customer->getInvoicingAddress();
        }
        if (empty($this->shipping_address) && $customer->getShippingAddress()) {
            $this->shipping_address = $customer->getShippingAddress();
        }
    }

    /**
     * Set customer.
     *
     * @param Siwapp\CustomerBundle\Entity\Customer $customer
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Set customer_name
     *
     * @param string $customerName
     */
    public function setCustomerName($customerName)
    {
        $this->customer_name = $customerName;
    }

    /**
     * Get customer_name
     *
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customer_name;
    }

    /**
     * Set customer_identification
     *
     * @param string $customerIdentification
     */
    public function setCustomerIdentification($customerIdentification)
    {
        $this->customer_identification = $customerIdentification;
    }

    /**
     * Get customer_identification
     *
     * @return string
     */
    public function getCustomerIdentification()
    {
        return $this->customer_identification;
    }

    /**
     * Set customer_email
     *
     * @param string $customerEmail
     */
    public function setCustomerEmail($customerEmail)
    {
        $this->customer_email = $customerEmail;
    }

    /**
     * Get customer_email
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->customer_email;
    }

    /**
     * Set invoicing_address
     *
     * @param text $invoicingAddress
     */
    public function setInvoicingAddress($invoicingAddress)
    {
        $this->invoicing_address = $invoicingAddress;
    }

    /**
     * Get invoicing_address
     *
     * @return text
     */
    public function getInvoicingAddress()
    {
        return $this->invoicing_address;
    }

    /**
     * Set shipping_address
     *
     * @param text $shippingAddress
     */
    public function setShippingAddress($shippingAddress)
    {
        $this->shipping_address = $shippingAddress;
    }

    /**
     * Get shipping_address
     *
     * @return text
     */
    public function getShippingAddress()
    {
        return $this->shipping_address;
    }

    /**
     * Set contact_person
     *
     * @param string $contactPerson
     */
    public function setContactPerson($contactPerson)
    {
        $this->contact_person = $contactPerson;
    }

    /**
     * Get contact_person
     *
     * @return string
     */
    public function getContactPerson()
    {
        return $this->contact_person;
    }

    /**
     * Set terms
     *
     * @param text $terms
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;
    }

    /**
     * Get terms
     *
     * @return text
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * Set notes
     *
     * @param text $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * Get notes
     *
     * @return text
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set base_amount
     *
     * @param decimal $baseAmount
     */
    public function setBaseAmount($baseAmount)
    {
        $this->base_amount = $baseAmount;
    }

    /**
     * Get base_amount
     *
     * @return decimal
     */
    public function getBaseAmount()
    {
        return $this->base_amount;
    }

    /**
     * Set discount_amount
     *
     * @param decimal $discountAmount
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discount_amount = $discountAmount;
    }

    /**
     * Get discount_amount
     *
     * @return decimal
     */
    public function getDiscountAmount()
    {
        return $this->discount_amount;
    }

    /**
     * Set net_amount
     *
     * @param decimal $netAmount
     */
    public function setNetAmount($netAmount)
    {
        $this->net_amount = $netAmount;
    }

    /**
     * Get net_amount
     *
     * @return decimal
     */
    public function getNetAmount()
    {
        return $this->net_amount;
    }

    /**
     * Set gross_amount
     *
     * @param decimal $grossAmount
     */
    public function setGrossAmount($grossAmount)
    {
        $this->gross_amount = $grossAmount;
    }

    /**
     * Get gross_amount
     *
     * @return decimal
     */
    public function getGrossAmount()
    {
        return $this->gross_amount;
    }

    /**
     * Set paid_amount
     *
     * @param decimal $paidAmount
     */
    public function setPaidAmount($paidAmount)
    {
        $this->paid_amount = $paidAmount;
    }

    /**
     * Get paid_amount
     *
     * @return decimal
     */
    public function getPaidAmount()
    {
        return $this->paid_amount;
    }

    /**
     * Set tax_amount
     *
     * @param decimal $taxAmount
     */
    public function setTaxAmount($taxAmount)
    {
        $this->tax_amount = $taxAmount;
    }

    /**
     * Get tax_amount
     *
     * @return decimal
     */
    public function getTaxAmount()
    {
        return $this->tax_amount;
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

    /**
     * Set serie
     *
     * @param Siwapp\CoreBundle\Entity\Series $series
     */
    public function setSeries(Series $series)
    {
        $this->series = $series;
    }

    /**
     * Get serie
     *
     * @return Siwapp\CoreBundle\Entity\Series
     */
    public function getSeries()
    {
        return $this->series;
    }

    /**
     * Add items
     *
     * @param Siwapp\InvoiceBundle\Entity\Item $item
     */
    public function addItem(Item $item)
    {
        $this->items[] = $item;
    }

    /**
     * Get items
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getItems()
    {
        return $this->items;
    }

    /** ########### CUSTOM METHODS ################## */


    public function __isset($name)
    {
        if (in_array($name, array_keys(get_object_vars($this)))) {
            return true;
        }

        return false;
    }

    /** ** RELATIONSHIPS ** */


    /**
     * removeItem
     * removes an item and recalculcates amounts
     *
     * @param mixed $mixed : can be an integer or an item instance
     *                       - if an integer, removes the item with
     *                         that position in the collection
     *                       - if an instance, removes that item
     * @author JoeZ99 <jzarate@gmail.com>
     */
    public function removeItem($mixed)
    {
        if ($mixed instanceof Item) {
            $items = $this->getItems();
            foreach ($items as $ref => $item) {
                if ($item === $mixed) {
                    $pro = $item->getProduct();
                    if ($pro instanceof Product){
                        if ($this instanceof Invoice){
                            $pro->setStock($pro->getStock() + $item->getQuantity());
                        }
                        
                        if($this instanceof InvoiceProvider){
                            $pro->setStock($pro->getStock() - $item->getQuantity());
                        }
                    }

                    unset($items[$ref]);
                    break;
                }
            }
        } elseif (is_int($mixed)) {
            unset($this->items[$mixed]);
        }

    }

    /* ** OTHER ** */

    private $decimals = null;

    public function getRoundedAmount($concept = 'gross')
    {
        if (!in_array($concept, array('base', 'discount', 'net', 'tax', 'gross'))) {
            return 0;
        }
        return round(call_user_func(array($this, Inflector::camelize('get_'.$concept.'_amount'))), $this->getDecimals());
    }

    private function getDecimals()
    {
        if (!$this->decimals) {
            $this->decimals = 2;
        }
        return $this->decimals;
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
                foreach ($this->getPayments() as $payment) {
                    $val += $payment->getAmount();
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

    public function checkAmounts()
    {
        $this->setBaseAmount($this->calculate('base_amount'));
        $this->setDiscountAmount($this->calculate('discount_amount'));
        $this->setNetAmount($this->getBaseAmount() - $this->getDiscountAmount());
        $this->setTaxAmount($this->calculate('tax_amount'));
        $rounded_gross = round(
            $this->getNetAmount() + $this->getTaxAmount(),
            $this->getDecimals()
        );
        $this->setGrossAmount($rounded_gross);

        return $this;
    }


    /** *********** LIFECYCLE CALLBACKS ************* */

    /**
     * @ORM\PreUpdate
     * @ORM\PrePersist
     */
    public function preSave(LifecycleEventArgs $args)
    {
        $this->checkStatus();
        $customers = $args->getEntityManager()->getRepository('SiwappCustomerBundle:Customer')->findBy([
            'name' => $this->getCustomerName(),
            'identification' => $this->getCustomerIdentification(),
        ]);
        $customer = reset($customers);
        if ($customer) {
            $this->setCustomer($customer);
        }
    }
}
