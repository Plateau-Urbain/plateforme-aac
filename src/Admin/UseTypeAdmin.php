<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use App\Entity\UseType;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use App\Entity\Category;

/** @extends AbstractAdmin<UseType> */
class UseTypeAdmin extends AbstractAdmin
{
    protected $baseRouteName = 'useType';
    protected $baseRoutePattern = 'useType';

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
            ->add('name', null, ['label' => "Type de projet"])
            ->add('isActive', null, [
                'label' => "Actif",
                'required' => false,
                'help' => "Décocher pour archiver : la valeur ne sera plus proposée dans les formulaires utilisateurs, mais reste visible pour les profils qui l'ont déjà sélectionnée.",
            ])

            ->end()

        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name', null, ['label' => "Type de projet"])
            ->add('isActive', null, ['label' => "Actif"])
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name', null, ['label' => "Type de projet"])
            ->add('isActive', null, [
                'label' => "Actif",
                'editable' => true,
            ])
        ;
    }

    
}
