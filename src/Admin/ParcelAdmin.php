<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use App\Entity\Parcel;

/** @extends AbstractAdmin<Parcel> */
class ParcelAdmin extends AbstractAdmin
{
    protected $baseRouteName = 'parcel';
    protected $baseRoutePattern = 'parcel';

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('floor', null, ['label' => 'Etage'])
            ->add('type')
            ->add('minSurface', null, ['label' => 'Surface min'])
            ->add('maxSurface', null, ['label' => 'Surface max'])
            ->add('disponibility', null, ['label' => 'Date de disponibilité'])
            ->end()

        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('minSurface')
            ->add('maxSurface')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('minSurface', null, ['label' => 'Surface min'])
            ->add('maxSurface', null, ['label' => 'Surface max'])
        ;
    }

    
}
