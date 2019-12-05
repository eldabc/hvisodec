<?php

namespace Siwapp\ProductBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Siwapp\CategoryBundle\Entity\Category;
use Siwapp\CategoryBundle\Repository\CategoryRepository;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category_id', EntityType::class, [
                'class' => Category::class,
                'query_builder' => function(CategoryRepository $repo) {
                    return $repo->createCategoryQueryBuilder();
                },
                'label' => 'form.category',
                'translation_domain' => 'SiwappProductBundle',
            ])
            ->add('reference', null, [
                'label' => 'form.reference',
                'translation_domain' => 'SiwappProductBundle',
            ])
            ->add('description', null, [
                'label' => 'form.description',
                'translation_domain' => 'SiwappProductBundle',
            ])
            ->add('price', null, [
                'label' => 'form.price',
                'translation_domain' => 'SiwappProductBundle',
            ])
            ->add('stock', null, [
                'label' => 'form.stock',
                'translation_domain' => 'SiwappProductBundle',
                'disabled' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Siwapp\ProductBundle\Entity\Product',
        ]);
    }
}
