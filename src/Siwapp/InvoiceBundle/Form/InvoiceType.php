<?php

namespace Siwapp\InvoiceBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Siwapp\CoreBundle\Form\AbstractInvoiceType;
use Siwapp\CoreBundle\Entity\Item;
use Siwapp\CoreBundle\Form\ItemType;

class InvoiceType extends AbstractInvoiceType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('issue_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'form.issue_date',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('due_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'form.due_date',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('forcefully_closed', null, [
                'label' => 'form.forcefully_closed',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('sent_by_email', null, [
                'label' => 'form.sent_by_email',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('domicile', null, [
                'label' => 'form.domicile',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('postal_code', TextType::class, [
                'required' => false,
                'label' => 'form.postal_code',
                'translation_domain' => 'SiwappCustomerBundle',
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'label' => 'form.location',
                'translation_domain' => 'SiwappCustomerBundle',
            ])
            ->add('province', TextType::class, [
                'required' => false,
                'label' => 'form.province',
                'translation_domain' => 'SiwappCustomerBundle',
            ])
            ->add('mandato', null, [
                'label' => 'form.mandato',
                'required' => false,
                'translation_domain' => 'SiwappCustomerBundle',
            ])
            ->add('fecha_mandato', DateType::class, [
                'widget' => 'single_text',
                'label' => 'form.fecha_mandato',
                'required' => false,
                'translation_domain' => 'SiwappCustomerBundle',
            ])
            ->add('bic', null, [
                'label' => 'form.bic',
                'required' => false,
                'translation_domain' => 'SiwappCustomerBundle',
            ])
            ->add('iban', null, [
                'label' => 'form.iban',
                'required' => false,
                'translation_domain' => 'SiwappCustomerBundle',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    	$resolver->setRequired([
    		'editing',
    		'default_invoice',
    	]);
    	
        $resolver->setDefaults([
            'data_class' => 'Siwapp\InvoiceBundle\Entity\Invoice',
        	'editing' => false,
        	'default_invoice' => null,
        ]);
    }
}
