<?php
// vim:expandtab:sw=4 softtabstop=4:

namespace App\Form;

use App\Entity\SpaceAttribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SpaceAttributeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'availability',
                  //'choice',
                  ChoiceType::class,
                [
                    'label' => 'Disponibilité',
                    'choices' => array_flip(SpaceAttribute::getAllStatus()),
                    'expanded' => true,
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
            'data_class' => SpaceAttribute::class
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
        return 'appbundle_space_attribute';
    }
}
