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
 * Form type for SpaceImage used as a document (PDF, DOC, DOCX).
 * Uses VichFileType instead of VichImageType to accept non-image files.
 * Sets a default fileType so validation uses document rules instead of image rules.
 */
class SpaceDocType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', VichFileType::class, [
            'label' => false,
            'required' => false,
            'download_uri' => false
        ]);

        $fileType = $options['file_type'];

        // Set the fileType on the SpaceImage entity BEFORE validation runs,
        // so that validateFile() applies document rules (PDF/DOC/DOCX, 10 Mo)
        // instead of the default image rules (JPEG/PNG/WebP, 600 Ko).
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($fileType) {
            $data = $event->getData();
            if ($data instanceof SpaceImage && $data->getFile() !== null) {
                $data->setFileType($fileType);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SpaceImage::class,
            'file_type' => SpaceImage::FILETYPE_DOCUMENT_AAC,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_spacedoc';
    }
}
