<?php

namespace App\Form;

use App\Entity\SpaceImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * Sonata-compatible form type for AAC/Plan PDF documents.
 * Unlike SpaceDocType (which is unmapped), this type is mapped to the Space entity
 * via virtual getDocAac()/setDocAac() and getDocPlan()/setDocPlan() methods.
 */
class SpaceDocAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', VichFileType::class, [
            'label'        => false,
            'required'     => false,
            'download_uri' => true,
            'allow_delete' => false,
        ]);

        $fileType = $options['file_type'];

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($fileType) {
            $data = $event->getData();
            if ($data instanceof SpaceImage && $data->getFile() !== null) {
                $data->setFileType($fileType);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SpaceImage::class,
            'file_type'  => SpaceImage::FILETYPE_DOCUMENT_AAC,
            'required'   => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'appbundle_spacedocadmin';
    }
}
