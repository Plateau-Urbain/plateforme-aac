<?php
// vim:expandtab:sw=4 softtabstop=4:

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VisitType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('visitDate', \Symfony\Component\Form\Extension\Core\Type\DateType::class, [
                'label' => 'Date de visite',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('startTime', \Symfony\Component\Form\Extension\Core\Type\TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endTime', \Symfony\Component\Form\Extension\Core\Type\TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
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
        return 'appbundle_visit';
    }
}


