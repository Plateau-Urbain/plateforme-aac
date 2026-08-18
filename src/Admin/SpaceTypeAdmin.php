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

    /** @var array<string, mixed> */
    protected array $datagridValues = [
        '_sort_order' => 'ASC',
        '_sort_by' => 'name',
    ];

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->with('General')
            ->add('name', null, ['label' => "Type d'espace"])
            ->add('isActive', null, [
                'label' => 'Actif',
                'required' => false,
                'help' => "Décocher pour archiver : la valeur ne sera plus proposée dans les formulaires, mais reste visible pour les espaces qui l'ont déjà sélectionnée.",
            ])
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name', null, ['label' => "Type d'espace"])
            ->add('isActive', null, ['label' => 'Actif'])
        ;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name', null, ['label' => "Type d'espace"])
            ->add('isActive', null, [
                'label' => 'Actif',
                'editable' => true,
            ])
        ;
    }
}
