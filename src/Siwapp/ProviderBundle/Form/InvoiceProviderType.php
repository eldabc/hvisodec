<?php

namespace Siwapp\ProviderBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Siwapp\CoreBundle\Entity\Item;
use Symfony\Component\Form\AbstractType;
use Doctrine\Common\Persistence\ObjectManager;

class InvoiceProviderType extends AbstractType
{
    private $manager;
    
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->editing = $options['editing'];

    	
        $builder
	        ->add('provider_name', null, [
	        		'label' => 'form.name',
	        		'translation_domain' => 'SiwappProviderBundle',
	        ])
	        ->add('provider_identification', null, [
	        		'label' => 'form.identification',
	        		'translation_domain' => 'SiwappProviderBundle',
	        ])
            ->add('gross_amount', null, [
                'label' => 'form.amount',
                'translation_domain' => 'SiwappProviderBundle',
            ])
            ->add('tax_amount', null, [
            		'label' => 'form.tax',
            		'translation_domain' => 'SiwappProviderBundle',
            ])
            ->add('issue_date', DateType::class, [
            		'widget' => 'single_text',
            		'required' => false,
            		'label' => 'form.issue_date',
            		'translation_domain' => 'SiwappProviderBundle',
                'required' => true,
            ])
            ->add('due_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'form.due_date',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'form.force_close',
                'translation_domain' => 'SiwappProviderBundle',
                'choices' => array(
                    'No' => '2',
                    'Si' => '1'
                )
            ])
            ->add('notes', TextareaType::class, [
            		'required' => false,
            		'label' => 'form.description',
            		'translation_domain' => 'SiwappProviderBundle',
            ])
            ->add('invoice', FileType::class, [
            		'label' => false,
            		'required' => $this->editing
            ])
        ;
            
        $builder->add('items', CollectionType::class, array(
            'entry_type' => 'Siwapp\CoreBundle\Form\ItemType',
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'by_reference' => false,
            'label' => false,
            'prototype_data' => new Item($this->manager->getRepository('SiwappCoreBundle:Tax')->findBy(['is_default' => 1])),
            
            'entry_options' => [
                // 'delete_empty' => true,
                'attr' => ['class' => 'see'],
            ],
        ));
            
		$builder->get('invoice')
            ->addModelTransformer(new CallbackTransformer(
            		function ($filename) {
            			return $filename ? new File($filename, false) : null;
            		},
            		function ($file) {
            			return $file;
            		}
            		))
            		;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    	$resolver->setRequired([
    			'editing'
    	]);
    	
        $resolver->setDefaults([
            'data_class' => 'Siwapp\ProviderBundle\Entity\InvoiceProvider',
        	'editing' => true,
            'default_invoice' => null,
            'product' => true
        ]);
    }
}
