<?php
// vim:expandtab:sw=4 softtabstop=4:
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationFileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // file => Symfony\Component\Form\Extension\Core\Type\FileType
        $builder
            ->add(
                'file',
                \Symfony\Component\Form\Extension\Core\Type\FileType::class,
                [
                    'label' => false
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
            'data_class' => \App\Entity\ApplicationFile::class,
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
        return 'application_file';
    }
}
