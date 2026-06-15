<?php

namespace App\Admin;

use App\Entity\UserDocument;
use App\Form\DocAdminType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

/**
 * SpaceImage admin.
 */
/** @extends AbstractAdmin<UserDocument> */
class UserDocumentAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('fileName');
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('type')
            ->add('file', DocAdminType::class, [
                'label' => 'Document',
                'required' => false,
            ]);
    }
}
