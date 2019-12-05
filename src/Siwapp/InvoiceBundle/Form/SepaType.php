<?php

namespace Siwapp\InvoiceBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Siwapp\InvoiceBundle\Entity\Invoice;

class SepaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date_from', DateType::class, [
                'label' => 'sepa.date_from',
                'translation_domain' => 'SiwappInvoiceBundle',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('date_to', DateType::class, [
                'label' => 'sepa.date_to',
                'translation_domain' => 'SiwappInvoiceBundle',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('date_creation', DateType::class, [
                'label' => 'sepa.date_creation',
                'translation_domain' => 'SiwappInvoiceBundle',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('date_payment', DateType::class, [
                'label' => 'sepa.date_payment',
                'translation_domain' => 'SiwappInvoiceBundle',
                'widget' => 'single_text',
                'required' => true,
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }
}
