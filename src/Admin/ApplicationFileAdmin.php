<?php

namespace App\Admin;

use App\Entity\ApplicationFile;
use App\Form\DocAdminType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/** @extends AbstractAdmin<ApplicationFile> */
class ApplicationFileAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('fileName');
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper->add('file', DocAdminType::class, [
            'label' => 'Document',
            'required' => false,
        ]);
    }
}
