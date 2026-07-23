<?php

namespace App\Admin;

use App\Entity\ApplicationFile;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * Fichiers joints d'une candidature — édition inline Sonata.
 *
 * @extends AbstractAdmin<ApplicationFile>
 */
class ApplicationFileAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('fileName');
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper->add('file', VichFileType::class, [
            'label' => 'Document',
            'required' => false,
            'download_uri' => true,
            'download_label' => static function ($file): string {
                if ($file instanceof ApplicationFile && $file->getFileName()) {
                    return (string) $file->getFileName();
                }

                return 'Télécharger';
            },
            'allow_delete' => true,
            'delete_label' => 'Supprimer le fichier',
            'asset_helper' => true,
        ]);
    }
}
