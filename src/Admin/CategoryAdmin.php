<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use App\Entity\Category;

/** @extends AbstractAdmin<Category> */
class CategoryAdmin extends AbstractAdmin
{
    protected $baseRouteName = 'category';
    protected $baseRoutePattern = 'category';

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
            ->add('name', null, ['label' => "Type d'usage"])
            ->add('isActive', null, [
                'label' => "Actif",
                'required' => false,
                'help' => "Décocher pour archiver : la valeur ne sera plus proposée dans les formulaires utilisateurs, mais reste visible pour les candidatures/profils qui l'ont déjà sélectionnée.",
            ])
            ->add('requiresErp', null, [
                'label' => "Réservé aux sites ERP",
                'required' => false,
                'help' => "Si coché, ce type d'usage ne sera proposé aux candidats que pour les espaces marqués comme ERP (Établissement Recevant du Public).",
            ])

            ->end()

        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name', null, ['label' => "Type d'usage"])
            ->add('isActive', null, ['label' => "Actif"])
            ->add('requiresErp', null, ['label' => "Réservé ERP"])
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name', null, ['label' => "Type d'usage"])
            ->add('isActive', null, [
                'label' => "Actif",
                'editable' => true,
            ])
            ->add('requiresErp', null, [
                'label' => "Réservé ERP",
                'editable' => true,
            ])
        ;
    }

    
}
