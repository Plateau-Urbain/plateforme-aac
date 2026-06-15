<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use App\Entity\SpaceType;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/** @extends AbstractAdmin<SpaceType> */
class SpaceTypeAdmin extends AbstractAdmin
{
    protected $baseRouteName = 'spacetype';
    protected $baseRoutePattern = 'spacetype';

    // setup the default sort column and order
    /** @var array<string, mixed> */
    protected array $datagridValues = [
        '_sort_order' => 'ASC',
        '_sort_by' => 'name',
    ];

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->with('General')
            ->add('name')

            ->end()

        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name')
        ;
    }

    
}
