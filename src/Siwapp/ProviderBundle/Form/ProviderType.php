<?php

namespace Siwapp\ProviderBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProviderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'label' => 'form.name',
                'translation_domain' => 'SiwappProviderBundle',
            ])
            ->add('identification', null, [
                'label' => 'form.identification',
                'translation_domain' => 'SiwappProviderBundle',
            ])
            ->add('email', null, [
                'label' => 'form.email',
                'translation_domain' => 'SiwappProviderBundle',
            ])
            ->add('contact_person', null, [
                'label' => 'form.contact_person',
                'translation_domain' => 'SiwappProviderBundle',
            ])
            ->add('invoicing_address', TextareaType::class, [
                'required' => false,
                'label' => 'form.invoicing_address',
                'translation_domain' => 'SiwappProviderBundle',
            ])
            ->add('shipping_address', TextareaType::class, [
                'required' => false,
                'label' => 'form.shipping_address',
                'translation_domain' => 'SiwappProviderBundle',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Siwapp\ProviderBundle\Entity\Provider',
        ]);
    }
}
