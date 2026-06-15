<?php

namespace App\Admin;

use App\Entity\SpaceAttribute;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/** @extends AbstractAdmin<SpaceAttribute> */
class SpaceAttributeAdmin extends AbstractAdmin
{
    // setup the default sort column and order
    /** @var array<string, mixed> */
    protected array $datagridValues = [
        '_sort_order' => 'ASC',
        '_sort_by' => 'attribute',
    ];

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('attribute', null, ['label' => 'Nom'])
	    ->add('availability', ChoiceType::class, [
		    'choices' => array_flip(SpaceAttribute::getAllStatus()),
		    'required' => true,
		    'label' => 'Disponibilité'
	    ])
            ->end();
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('attribute')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('attribute')
        ;
    }

    
}
