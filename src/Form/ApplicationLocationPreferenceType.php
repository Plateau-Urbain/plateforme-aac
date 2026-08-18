<?php

namespace App\Form;

use App\Entity\ApplicationLocationPreference;
use App\Entity\SpaceLocation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
                'empty_data' => $adminMode ? 1 : '',
                'attr' => $adminMode
                    ? ['min' => 1]
                    : ['class' => 'js-location-preference-rank'],
            ], static fn ($v) => $v !== null))
            ->add('excluded', $adminMode ? CheckboxType::class : HiddenType::class, [
                'label' => $adminMode ? 'Ne m\'intéresse pas' : false,
                'required' => false,
                'attr' => $adminMode ? [] : ['class' => 'js-location-preference-excluded'],
            ])
        ;

        // HiddenType soumet une chaîne ("0"/"1") ; on convertit vers bool pour l'entité.
        if (!$adminMode) {
            $builder->get('excluded')->addModelTransformer(new CallbackTransformer(
                static fn ($value): string => $value ? '1' : '0',
                static fn ($value): bool => $value === '1' || $value === true || $value === 1
            ));
        }
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
