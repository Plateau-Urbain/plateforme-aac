<?php
// vim:expandtab:sw=4 softtabstop=4:

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParcelType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('floor', null, ['label' => 'Étage', 'placeholder' => 'Étage' ,'attr' => ['class' => 'form-control']])
            ->add('type', null, ['label' => 'Type de locaux', 'placeholder' => 'Type de locaux', 'attr' => ['class' => 'form-control']])
            ->add('minSurface', null, [
                'label' => 'Surface minimale (m²)', 
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Surface minimale en m²'
                ]
            ])
            ->add('maxSurface', null, [
                'label' => 'Surface maximale (m²)', 
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Surface maximale en m²'
                ]
            ])
            // date => Symfony\Component\Form\Extension\Core\Type\DateType
            ->add(
                'disponibility',
                \Symfony\Component\Form\Extension\Core\Type\DateType::class,
                [
                'label' => 'Disponibilité',
                'widget' => 'single_text',
                //'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'form-control',
                    //'data-provide' => 'datepicker'
                ]
            ]
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Parcel::class
        ]);
    }

    // The FormTypeInterface::getName()
    // method is deprecated since Symfony 2.8 and will be removed in 3.0.
    // Remove it from your classes. Use getBlockPrefix() if you want
    // to customize the template block prefix.
    // This method will be added to the FormTypeInterface with Symfony 3.0
    /**
     * @return string
     */
    //public function getName()
    public function getBlockPrefix()
    {
        return 'appbundle_parcel';
    }
}
