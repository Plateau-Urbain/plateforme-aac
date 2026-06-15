<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpaceVisitType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('visitDate', DateType::class, [
                'label' => 'Date de visite',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Date de visite'
                ],
                'required' => false
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Heure de début'
                ],
                'required' => false
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Heure de fin'
                ],
                'required' => false
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\SpaceVisit::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'appbundle_spacevisit';
    }
}
