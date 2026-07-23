<?php

namespace App\Form;

use App\Entity\ApplicationLocationPreference;
use App\Entity\SpaceLocation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationLocationPreferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $adminMode = (bool) $options['admin_mode'];

        $builder
            ->add('location', EntityType::class, [
                'class' => SpaceLocation::class,
                'choices' => $options['locations'],
                'label' => $adminMode ? 'Site' : false,
                'attr' => $adminMode ? [] : ['class' => 'js-preference-location-input'],
            ])
            ->add('rank', $adminMode ? IntegerType::class : HiddenType::class, array_filter([
                'label' => $adminMode ? 'Rang' : false,
                'required' => false,
                'empty_data' => $adminMode ? 1 : '1',
                'attr' => $adminMode
                    ? ['min' => 1]
                    : ['class' => 'js-location-preference-rank'],
            ], static fn ($v) => $v !== null))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApplicationLocationPreference::class,
            'locations' => [],
            'admin_mode' => false,
        ]);

        $resolver->setAllowedTypes('locations', 'array');
        $resolver->setAllowedTypes('admin_mode', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'appbundle_applicationlocationpreference';
    }
}
