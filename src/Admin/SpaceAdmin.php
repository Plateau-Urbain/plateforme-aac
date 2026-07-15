<?php

namespace App\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use App\Entity\Space;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Repository\UserRepository;
use App\Form\SpaceDocumentType;
use App\Form\SpaceImageType;
use App\Form\SpaceVisitType;
use App\Form\SpaceDocAdminType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/** @extends AbstractAdmin<Space> */
class SpaceAdmin extends AbstractAdmin
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('show');
    }

    protected function configure(): void
    {
        $this->setTemplates([
            'outer_list_rows_list' => 'Admin/Space/list_outer_rows_list.html.twig',
        ]);
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        assert($query instanceof ProxyQuery);
        $alias = $query->getRootAliases()[0];
        $query->leftJoin($alias.'.owner', 'space_owner')->addSelect('space_owner');

        return $query;
    }

    /** @param iterable<\App\Entity\SpaceImage|\App\Entity\SpaceDocument|\App\Entity\SpaceVisit> $children */
    public function syncSpace(\App\Entity\Space $space, iterable $children): void
    {
        foreach ($children as $child) {
            $child->setSpace($space);
        }
    }
    public function prePersist(object $object): void
    {
        $this->syncSpace($object, $object->getPics());
        $this->syncSpace($object, $object->getDocuments());
        $this->syncSpace($object, $object->getVisits());
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(object $object): void
    {
        $this->syncSpace($object, $object->getPics());
        $this->syncSpace($object, $object->getDocuments());
        $this->syncSpace($object, $object->getVisits());
    }

    /**
     * {@inheritdoc}
     * Supprime les applications associées avant de supprimer l'espace
     */
    public function preRemove(object $object): void
    {
        $em = $this->em;

        $applications = $em->getRepository(\App\Entity\Application::class)->findBy(['space' => $object]);

        foreach ($applications as $application) {
            $applicationFiles = $em->getRepository(\App\Entity\ApplicationFile::class)->findBy(['application' => $application]);
            foreach ($applicationFiles as $file) {
                $em->remove($file);
            }
            $em->remove($application);
        }
        // Pas de flush() ici : Sonata commitera tout (fichiers + candidatures + espace)
        // en une seule transaction atomique via son propre flush().
    }

    protected $baseRouteName = 'property';
    protected $baseRoutePattern = 'property';

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'created';
    }

    protected function configureFormOptions(array &$formOptions): void
    {
        $formOptions['validation_groups'] = static function (FormInterface $form): array {
            $space = $form->getData();
            $groups = ['save'];

            if ($space instanceof Space && $space->isMultiLocation()) {
                $groups[] = 'multi_location';
            } else {
                $groups[] = 'standard';
            }

            return $groups;
        };
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->with('General')
            ->add('name', null, ['label' => "Nom de l'espace"])
            ->add('managedByLabel', null, [
                'label' => "Texte du badge (ex: Géré, Commercialisé...)",
                'required' => false,
                'help' => 'Par défaut: Géré'
            ])
            ->add('owner', null, [
                'required' => true,
                'query_builder' => fn (UserRepository $repository) => $repository->createProprietairesQueryBuilder(),
            ], [
                'label' => "Propriétaire de l'espace",
                'admin_code' => 'app.admin.owner',
            ])
            ->add('zipCode', null, ['label' => 'Code postal', 'required' => false])
            ->add('city', null, ['label' => 'Ville'])
            ->add('limitAvailability', null, ['label' => 'Date limite de candidature', 'required' => false])
            ->add('availability', null, ['label' => 'Durée du projet'])
            ->add('type', null, ['label' => 'Type de locaux', 'required' => true])
            ->add('description', null, ['label' => 'Description', 'attr' => ['class' => 'trumbowyg']])
            ->add('activityDescription', null, ['label' => 'Activités recherchées', 'attr' => ['class' => 'trumbowyg']])
            ->add('price', null, ['label' => 'Prix au m² mensuel', 'required' => false])
            ->add('priceText', null, ['label' => 'Prix personnalisé (texte libre)', 'required' => false])
            ->add('nbSpaces', null, ['label' => "Nombre d'espaces", 'required' => false])
            ->add('minSpace', null, ['label' => 'Surface minimale (m²)', 'required' => false])
            ->add('maxSpace', null, ['label' => 'Surface maximale (m²)', 'required' => false])

            ->end()
            // Section "Prestations et services" désactivée — les tags/attributs ne sont plus utilisés en front-end
            // ->with('Prestations et services')
            // ->add('tags', CollectionType::class, array(
            //         'entry_type' => SpaceAttributeAdminType::class,
            //         'allow_delete' => true,
            //         'allow_add' => true,
            //         'by_reference' => false,
            //         'label' => 'Attributs',
            //     ),
            //     array(
            //         'edit' => 'inline',
            //         'inline' => 'table',
            //     ))
            // ->end()
            // Section "Lots" désactivée — la gestion des lots a été retirée du formulaire front-end
            // ->with('Lots')
            // ->add('parcels', CollectionType::class, array(
            //     'entry_type' => ParcelType::class,
            //     'allow_delete' => true,
            //     'allow_add' => true,
            //     'by_reference' => false,
            //     'label' => 'Lots',
            // ), array(
            //     'edit' => 'inline',
            //     'inline' => 'table',
            // ))
            // ->end()
            ->with('Photos')

            ->add('pics', CollectionType::class, [
                    'entry_type' => SpaceImageType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'by_reference' => false,
                    'label' => 'Photos',
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                ])

            ->end()
            ->with('Documents PDF (propriétaire)')

            ->add('docAac', SpaceDocAdminType::class, [
                'label'     => "Appel à candidature (PDF)",
                'required'  => false,
                'file_type' => 'document_aac',
            ])
            ->add('docPlan', SpaceDocAdminType::class, [
                'label'     => "Répartition des espaces (PDF)",
                'required'  => false,
                'file_type' => 'document_plan',
            ])
            ->add('docFaq', SpaceDocAdminType::class, [
                'label'     => "F.A.Q (PDF)",
                'required'  => false,
                'file_type' => 'document_faq',
            ])

            ->end()
            ->with('Documents requis (candidature)')

            ->add('documents', CollectionType::class, [
                    'entry_type' => SpaceDocumentType::class,
                    'by_reference' => false,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'label' => false
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                ])

            ->end()

            ->with('Visites programmées')

            ->add('visits', CollectionType::class, [
                    'entry_type' => SpaceVisitType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'by_reference' => false,
                    'label' => false,
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                ])

            ->end()

            ->with('Publication')
                ->add('enabled', ChoiceType::class, ['label' => 'En ligne', 'required' => false, 'choices' => ['Oui' => true, 'Non' => false], 'placeholder' => false])
                ->add('closed', ChoiceType::class, ['label' => 'Clôturé', 'required' => false, 'choices' => ['Oui' => true, 'Non' => false], 'placeholder' => false])
            ->end()

        ;

        $formMapper->getFormBuilder()->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $space = $event->getData();
            $form = $event->getForm();

            if (!$space instanceof Space || !$space->isMultiLocation()) {
                return;
            }

            foreach (['zipCode', 'limitAvailability', 'nbSpaces', 'minSpace', 'maxSpace'] as $field) {
                if ($form->has($field)) {
                    $form->remove($field);
                }
            }

            if ($form->has('city')) {
                $form->remove('city');
                $form->add('city', null, [
                    'label' => 'Commune ou territoire',
                    'required' => true,
                ]);
            }
        });
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name', null, ['label' => 'Nom'])
            ->add('owner', null, [
                'label' => 'Propriétaire',
                'admin_code' => 'app.admin.owner',
                'associated_property' => 'company',
            ])
            ->add('city', null, ['label' => 'Ville'])
            ->add('type', null, ['label' => 'Type de locaux'])
            ->add('enabled', null, ['label' => 'En ligne'])
            ->add('submitted', null, ['label' => 'Soumis'])
            ->add('closed', null, ['label' => 'Clôturé'])
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name', null, [
                'label' => 'Nom',
                'route' => ['name' => 'edit'],
            ])
            ->add('owner', null, [
                'label' => 'Propriétaire',
                'admin_code' => 'app.admin.owner',
                'associated_property' => 'company',
            ])
            ->add('city', null, ['label' => 'Ville'])
            ->add('limitAvailability', null, [
                'label' => 'Date limite de candidature',
                'template' => 'Admin/Space/list_limit_availability.html.twig',
                'sortable' => true,
            ])
            ->add('enabled', null, ['label' => 'En ligne'])
            ->add('submitted', null, ['label' => 'Soumis'])
            ->add('closed', null, ['label' => 'Clôturé'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Actions',
                'actions' => [
                    'edit'   => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->with('General')
                ->add('name', null, ['label' => "Nom de l'espace"])
                ->add('owner', null, [
                    'label' => "Propriétaire de l'espace",
                    'admin_code' => 'app.admin.owner',
                    'associated_property' => 'company',
                ])
                ->add('zipCode', null, ['label' => 'Code postal'])
                ->add('city', null, ['label' => 'Ville'])
                ->add('address', null, ['label' => 'Adresse'])
                ->add('limitAvailability', null, ['label' => 'Date limite de candidature'])
                ->add('availability', null, ['label' => 'Durée du projet'])
                ->add('type', null, ['label' => 'Type de locaux'])
                ->add('description', null, ['label' => 'Description'])
                ->add('activityDescription', null, ['label' => 'Activités recherchées'])
                ->add('price', null, ['label' => 'Prix au m² mensuel'])
                ->add('priceText', null, ['label' => 'Prix personnalisé (texte libre)'])
                ->add('nbSpaces', null, ['label' => "Nombre d'espaces"])
                ->add('minSpace', null, ['label' => 'Surface minimale (m²)'])
                ->add('maxSpace', null, ['label' => 'Surface maximale (m²)'])
            ->end()
            ->with('Prestations et services')
                ->add('tags', null, ['label' => 'Attributs', 'associated_property' => 'id'])
            ->end()
            ->with('Lots')
                ->add('parcels', null, ['label' => 'Lots', 'associated_property' => 'id'])
            ->end()
            ->with('Photos')
                ->add('pics', null, ['label' => 'Photos', 'associated_property' => 'id'])
            ->end()
            ->with('Documents')
                ->add('documents', null, ['label' => 'Documents requis (candidature)', 'associated_property' => 'id'])
            ->end()
            ->with('Publication')
                ->add('enabled', null, ['label' => 'En ligne'])
                ->add('closed', null, ['label' => 'Clôturé'])
                ->add('submitted', null, ['label' => 'Soumis'])
            ->end();
    }

    

    
}
