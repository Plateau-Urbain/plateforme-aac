<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationLocationPreferenceFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('site', ChoiceType::class, [
                'label' => 'Filtrer par site',
                'choices' => $options['site_choices'],
                'required' => false,
                'placeholder' => 'Tous les sites',
            ])
            ->add('rank', ChoiceType::class, [
                'label' => 'Au rang de préférence',
                'choices' => $options['rank_choices'],
                'required' => false,
                'placeholder' => 'N\'importe quel rang',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'site_choices' => [],
            'rank_choices' => [],
        ]);

        $resolver->setAllowedTypes('site_choices', 'array');
        $resolver->setAllowedTypes('rank_choices', 'array');
    }
}
