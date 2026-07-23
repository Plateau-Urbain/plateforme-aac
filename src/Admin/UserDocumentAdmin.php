<?php

namespace App\Admin;

use App\Entity\UserDocument;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * Documents utilisateur (KBIS, pièce d'identité) — édition inline Sonata.
 *
 * @extends AbstractAdmin<UserDocument>
 */
class UserDocumentAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('fileName')
            ->add('type', null, ['label' => 'Type']);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'kbis' => UserDocument::KBIS_TYPE,
                    'id' => UserDocument::ID_TYPE,
                ],
                'required' => true,
            ])
            ->add('file', VichFileType::class, [
                'label' => 'Document',
                'required' => false,
                'download_uri' => true,
                'download_label' => static function ($document): string {
                    if ($document instanceof UserDocument && $document->getFileName()) {
                        return (string) $document->getFileName();
                    }

                    return 'Télécharger';
                },
                'allow_delete' => true,
                'delete_label' => 'Supprimer le fichier',
                'asset_helper' => true,
            ]);
    }
}
