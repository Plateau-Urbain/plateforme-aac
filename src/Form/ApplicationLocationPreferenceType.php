<?php

namespace App\Form;

use App\Entity\ApplicationLocationPreference;
use App\Entity\SpaceLocation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationLocationPreferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('location', EntityType::class, [
                'class' => SpaceLocation::class,
                'choices' => $options['locations'],
                'label' => false,
                'attr' => ['class' => 'js-preference-location-input'],
            ])
            ->add('rank', HiddenType::class, [
                'attr' => ['class' => 'js-location-preference-rank'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApplicationLocationPreference::class,
            'locations' => [],
        ]);

        $resolver->setAllowedTypes('locations', 'array');
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_applicationlocationpreference';
    }
}
