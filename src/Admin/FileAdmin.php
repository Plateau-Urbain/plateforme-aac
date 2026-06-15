<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * SpaceImage admin.
 */
/** @extends AbstractAdmin<object> */
class FileAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('fileName');
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('file', VichImageType::class, [
                'required' => false
            ]);
    }
}
