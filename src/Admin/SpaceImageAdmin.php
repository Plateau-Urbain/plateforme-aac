<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Vich\UploaderBundle\Form\Type\VichImageType;
use App\Entity\SpaceImage;

/**
 * SpaceImage admin.
 */
/** @extends AbstractAdmin<SpaceImage> */
class SpaceImageAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('fileName')
            ->add('fileType', ChoiceType::class, [
                'choices' => [
                    SpaceImage::FILETYPE_IMAGE => 'Image',
                    SpaceImage::FILETYPE_DOCUMENT_PLAN => 'Plan',
                    SpaceImage::FILETYPE_DOCUMENT_AAC => 'AAC'
                ],
                'label' => 'Type'
            ])
            ->add('space', null, ['label' => 'Espace']);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('file', VichImageType::class, [
                'required' => false,
            ])
            ->add('fileType', ChoiceType::class, [
                'choices' => [
                    SpaceImage::FILETYPE_IMAGE => 'Image',
                    SpaceImage::FILETYPE_DOCUMENT_PLAN => 'Plan',
                    SpaceImage::FILETYPE_DOCUMENT_AAC => 'AAC'
                ],
                'label' => 'Type de fichier',
                'required' => true
            ])
            ->add('space', null, ['label' => 'Espace']);
    }
}
