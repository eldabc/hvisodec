<?php

namespace Siwapp\ProviderBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use Siwapp\ProviderBundle\Entity\InvoiceProvider;

class SearchInvoiceProviderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('provider', null, [
            		'label' => 'search.provider',
            		'translation_domain' => 'SiwappProviderBundle',
            		'required' => false,
            ])
            ->add('date_from', DateType::class, [
            		'widget' => 'single_text',
            		'required' => false,
            		'label' => 'search.date_from',
            		'translation_domain' => 'SiwappProviderBundle',
            ])
            ->add('date_to', DateType::class, [
            		'widget' => 'single_text',
            		'required' => false,
            		'label' => 'search.date_to',
            		'translation_domain' => 'SiwappProviderBundle',
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
