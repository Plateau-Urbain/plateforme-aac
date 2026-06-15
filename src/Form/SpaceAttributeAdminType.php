<?php
// vim:expandtab:sw=4 softtabstop=4:

namespace App\Form;

use App\Entity\SpaceAttribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SpaceAttributeAdminType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('attribute', null)
            ->add('availability', ChoiceType::class, [
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

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'appbundle_space_attribute';
    }
}
