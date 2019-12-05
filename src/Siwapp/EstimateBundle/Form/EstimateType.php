<?php

namespace Siwapp\EstimateBundle\Form;

use Siwapp\CoreBundle\Form\AbstractInvoiceType;
use Siwapp\EstimateBundle\Entity\Estimate;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class EstimateType extends AbstractInvoiceType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('issue_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'form.issue_date',
                'translation_domain' => 'SiwappEstimateBundle',
            ])
            ->add('sent_by_email', null, [
                'label' => 'form.sent_by_email',
                'translation_domain' => 'SiwappInvoiceBundle',
            ])
            ->add('iban', HiddenType::class, [
                'label' => false,
            ])
            ->add('bic', HiddenType::class, [
                'label' => false,
            ])
            ->add('mandato', HiddenType::class, [
                'label' => false,
            ])
            ->add('fecha_mandato', DateType::class, [
                'label' => false,
                'attr' => array('style' => "display:none"),
            ])
        ;

        $choices = array(
            'estimate.pending' => Estimate::PENDING,
            'estimate.approved' => Estimate::APPROVED,
            'estimate.rejected' => Estimate::REJECTED
        );

        if ( $builder->getData()->isDraft() )
        {
            $choices = array_merge(array('' => Estimate::DRAFT), $choices);
        }

        //if (!$builder->getData()->isDraft()) {
        if ($builder->getData()->getNumber() > 0) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'form.status',
                'translation_domain' => 'SiwappEstimateBundle',
                'choices' => $choices,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'editing',
            'quantity_item_zero',
        ]);

        $resolver->setDefaults([
            'data_class' => 'Siwapp\EstimateBundle\Entity\Estimate',
            'editing' => false,
            'quantity_item_zero' => false,
        ]);
    }
}
