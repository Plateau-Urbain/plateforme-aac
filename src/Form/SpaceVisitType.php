<?php

namespace App\Form;

use App\Entity\Space;
use App\Entity\SpaceLocation;
use App\Entity\SpaceVisit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpaceVisitType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['multi_location'] && $options['space'] instanceof Space) {
            $builder->add('location', EntityType::class, [
                'class' => SpaceLocation::class,
                'choices' => $options['space']->getLocations()->toArray(),
                'choice_label' => 'name',
                'label' => 'Lieu',
                'placeholder' => 'Choisir un lieu',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ]);
        }

        $builder
            ->add('visitDate', DateType::class, [
                'label' => 'Date de visite',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Date de visite',
                ],
                'required' => false,
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Heure de début',
                ],
                'required' => false,
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Heure de fin',
                ],
                'required' => false,
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SpaceVisit::class,
            'multi_location' => false,
            'space' => null,
        ]);

        $resolver->setAllowedTypes('multi_location', 'bool');
        $resolver->setAllowedTypes('space', ['null', Space::class]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'appbundle_spacevisit';
    }
}
