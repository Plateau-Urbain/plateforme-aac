<?php
//  vim:expandtab:sw=4 softtabstop=4:

namespace App\Form;

use App\Entity\UserDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Vich\UploaderBundle\Form\Type\VichFileType;

class UserDocumentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Accessing type "file" by its string name is deprecated since
        // Symfony 2.8 and will be removed in 3.0.
        // Use the fully-qualified type class name
        // "Symfony\Component\Form\Extension\Core\Type\FileType" instead.
        $builder->add('file', VichFileType::class, [
            'label' => false,
            'download_label' => true
                ]
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\UserDocument::class,
            'required' => false
        ]);
    }

    // AppBundle\Form\UserDocumentType: The FormTypeInterface::getName()
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
        return 'user_document';
    }
}
