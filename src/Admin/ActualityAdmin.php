<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use App\Entity\Actuality;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Vich\UploaderBundle\Form\Type\VichImageType;

/** @extends AbstractAdmin<Actuality> */
class ActualityAdmin extends AbstractAdmin
{
    /** @var array<string, mixed> */
    protected array $datagridValues = [
        '_sort_order' => 'ASC',
        '_sort_by' => 'date',
    ];

    /** @param DatagridMapper<Actuality> $datagridMapper */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('title')
            ->add('published')
        ;
    }

    /** @param ListMapper<Actuality> $listMapper */
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('title')
            ->add('date')
            ->add('published')
        ;
    }

    /** @param FormMapper<Actuality> $formMapper */
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->with('General')
            ->add('title', null, ['label' => 'Titre'])
            ->add('subtitle', null, ['label' => 'Sous-titre'])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('link', null, ['label' => 'Lien'])
            ->add('image', VichImageType::class, ['label' => 'Image', 'required' => false])
            ->add('published', ChoiceType::class, ['label' => 'Publié ?', 'choices' => ['Oui' => true, 'Non' => false]])

            ->end()
        ;
    }
}
