<?php

namespace Siwapp\CoreBundle\Form;

use Doctrine\Common\Persistence\ObjectManager;
use Siwapp\CoreBundle\Entity\Item;
use Siwapp\CoreBundle\Entity\Series;
use Siwapp\CoreBundle\Repository\AbstractInvoiceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractInvoiceType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('customer_name', null, [
                'label' => 'form.customer_name',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('customer_identification', null, [
                'label' => 'form.customer_identification',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('customer_email', null, [
                'label' => 'form.customer_email',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('invoicing_address', TextType::class, [
                'attr' => ['rows' => 3],
                'label' => 'form.invoicing_address',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('shipping_address', null, [
                'attr' => ['rows' => 3],
                'label' => 'form.shipping_address',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('postal_code', null, [
                'attr' => ['rows' => 3],
                'label' => 'form.postal_code',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('location', null, [
                'attr' => ['rows' => 3],
                'label' => 'form.location',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('province', null, [
                'attr' => ['rows' => 3],
                'label' => 'form.province',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('contact_person', null, [
                'label' => 'form.contact_person',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('terms', null, [
                'attr' => ['rows' => 5],
                'label' => 'form.terms',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('notes', null, [
                'attr' => ['rows' => 5],
                'label' => 'form.notes',
                'translation_domain' => 'SiwappInvoiceBundle',
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
            // 'entry_options' => [
            //     'attr' => ['class' => ''],
            // ],
        ));
        
        $defaultInvoiceSerie = isset($options['default_invoice']) ? $options['default_invoice'] : null;
        $serieId = $builder->getData()->getSeries() !== null ? $builder->getData()->getSeries()->getId() : 0;
        
        if ( $defaultInvoiceSerie != null )
        {
        	$builder->add('series', EntityType::class, array(
        			'class' => 'SiwappCoreBundle:Series',
        			'choice_label' => 'name',
        			'choice_attr' => function(Series $val, $key, $index) use ($defaultInvoiceSerie) {
        			if ( $defaultInvoiceSerie->getId() == $val->getId() )
        				return ['selected' => 'selected'];
        				
        				return array();
        			},
        			'label' => 'form.series',
        			'translation_domain' => 'SiwappInvoiceBundle',
        			'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($serieId) {
        			return $er->createQueryBuilder('s')
        			->where('s.enabled = :enabled')
        			->orWhere('s.id = :id_to_exclude')
        			->setParameter('enabled', true)
        			->setParameter('id_to_exclude', $serieId);
        			},
        			));
        }
        else
        {
        	$builder->add('series', EntityType::class, array(
        			'class' => 'SiwappCoreBundle:Series',
        			'choice_label' => 'name',
        			'label' => 'form.series',
        			'translation_domain' => 'SiwappInvoiceBundle',
        			'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($serieId) {
        			return $er->createQueryBuilder('s')
        			->where('s.enabled = :enabled')
        			->orWhere('s.id = :id_to_exclude')
        			->setParameter('enabled', true)
        			->setParameter('id_to_exclude', $serieId);
        			},
        			));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    	$resolver->setRequired([
    			'editing',
    			'default_invoice',
    			'quantity_item_zero',
    	]);
    	
    	$resolver->setDefaults([
    			'data_class' => 'Siwapp\CoreBundle\Entity\AbstractInvoice',
    			'editing' => false,
    			'default_invoice' => null,
    			'quantity_item_zero' => false,
    	]);
    }
}
