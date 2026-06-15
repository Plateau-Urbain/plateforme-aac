<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use App\Entity\SpaceDocument;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

/**
 * SpaceDocument admin.
 */
/** @extends AbstractAdmin<SpaceDocument> */
class SpaceDocumentAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name');
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('name', null, [
                'required' => true,
            ]);
    }
}
