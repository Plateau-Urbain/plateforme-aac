<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\Type\CollectionType;
use App\Form\ApplicationFileType;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\Application;
use App\Entity\User;
use App\Repository\UserRepository;

/** @extends AbstractAdmin<Application> */
class ApplicationAdmin extends AbstractAdmin
{
    protected $baseRouteName = 'candidature';
    protected $baseRoutePattern = 'candidature';

    // setup the default sort column and order
    /** @var array<string, mixed> */
    protected array $datagridValues = [
        '_sort_order' => 'desc',
        '_sort_by' => 'created',
    ];

    /**
     * Configure les routes personnalisées
     */
    public function prePersist(object $object): void
    {
        $this->ensureProjectHolder($object);
    }

    public function preUpdate(object $object): void
    {
        $this->ensureProjectHolder($object);
    }

    private function ensureProjectHolder(object $object): void
    {
        if (!$object instanceof Application) {
            return;
        }

        $holder = $object->getProjectHolder();
        if ($holder instanceof User && null !== $holder->getId()) {
            return;
        }

        if ($holder instanceof User && null === $holder->getId()) {
            $object->setProjectHolder($holder);
        }
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        assert($query instanceof ProxyQuery);
        $alias = $query->getRootAliases()[0];
        // LEFT JOINs pour le tri/filtre — sans addSelect() pour éviter l'hydratation
        // eager de milliers d'objets liés en mémoire lors de l'affichage de la liste.
        $query->leftJoin($alias.'.projectHolder', 'application_holder');
        $query->leftJoin($alias.'.space', 'application_space');
        $query->leftJoin('application_space.owner', 'application_space_owner');

        return $query;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('show');
        $collection->add('select_export_fields', 'select-export-fields');
        $collection->add('custom_export', 'custom-export');
        $collection->add('help_filters', 'help-filters-export');
        $collection->add('statistics', 'statistics');
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $application = $this->getSubject();
        $hasProjectHolder = $application instanceof Application
            && null !== $application->getProjectHolder();

        $formMapper
            ->with('General')
            ->add('status', ChoiceType::class, ['label' => 'Statut', 'choices' => Application::getStatusLabels()])
            ->add('space', null, ['label' => 'Espace'])
            ->add('projectHolder', null, [
                'query_builder' => fn (UserRepository $repository) => $repository->createPorteursQueryBuilder(),
            ], [
                'label' => 'Porteur de projet',
                'admin_code' => 'app.admin.project_holder',
            ])
            ->add('name', null, ['label'=>"Nom du projet"] )
            ->add('category', null, ['label'=>"Type d'usage",'required'=> true])
        ;

        if ($hasProjectHolder) {
            $formMapper->add('projectHolder.companyStatus', null, ['label' => 'Statut juridique (profil)']);
        }

        $formMapper
            ->add('companyStatus', ChoiceType::class, [
                'label' => 'Statut juridique (candidature)',
                'choices' => Application::getApplicationCompanyStatuses(),
                'required' => false,
            ])
        ;

        if ($hasProjectHolder) {
            $formMapper->add('projectHolder.wishedSize', null, ['label'=> 'Surface souhaitée (profil) (m²)']);
        }

        $formMapper
            ->add('wishedSize', null, ['label'=> 'Surface souhaitée (candidature) (m²)'])
            ->add('lengthOccupation', null, ['label'=> 'Durée d\'occupation'])
            ->add('lengthTypeOccupation', ChoiceType::class, ['choices' => Application::getAllLengthType(), 'label'=> 'Durée d\'occupation'])
            ->add('startOccupation', DateType::class, ['label'=>"Date d'entrée souhaitée"])
            ->add('description', null, ['label'=>"Description du projet"])
            ->add('openToGlobalProject', ChoiceType::class, ['label'=> "Ouvert à faire partie d'un projet collectif", 'choices' => ['Oui' => true, 'Non' => false]])
            ->add('contribution', null, ['label'=> "Contribution au projet global du propriétaire"])
            ->end()
            ->with('Documents')
            ->add('files', CollectionType::class, [
                'entry_type' => ApplicationFileType::class,
                'by_reference' => false,
                'allow_delete' => true,
                'allow_add' => true,
                'label' => 'Documents',
            ], [
                'edit' => 'inline',
                'inline' => 'table',
            ])
            ->end()

        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name', null, ['label' => 'Nom du projet'])
            ->add('status', ChoiceFilter::class, [
                'label' => 'Statut',
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => Application::getStatusLabels(),
                ],
            ])
            ->add('selected', null, ['label' => 'Sélectionné'])
            ->add('category', null, ['label' => "Type d'usage"])
            ->add('projectHolder', null, [
                'field_options' => [
                    'query_builder' => fn (UserRepository $repository) => $repository->createPorteursQueryBuilder(),
                ],
            ], [
                'label' => 'Porteur de projet',
                'admin_code' => 'app.admin.project_holder',
            ])
            ->add('space', null, ['label' => 'Espace'])
            ->add('created', DateTimeRangeFilter::class, ['label' => 'Date de création'])
            ->add('startOccupation', DateRangeFilter::class, ['label' => 'Date d\'entrée souhaitée'])
            ->add('projectHolder.companyStatus', null, ['label' => 'Statut juridique (profil)'])
            ->add('companyStatus', null, ['label' => 'Statut juridique (candidature)'])
            ->add('projectHolder.wishedSize', null, ['label' => 'Surface souhaitée (profil) (m²)'])
            ->add('wishedSize', null, ['label' => 'Surface souhaitée (candidature) (m²)'])
            ->add('openToGlobalProject', null, ['label' => 'Ouvert au projet collectif'])
        ;
    }
    
    // Fields to be shown on show page
    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->with('Informations générales', ['class' => 'col-md-6'])
                ->add('id', null, ['label' => 'ID'])
                ->add('name', null, ['label' => 'Nom du projet'])
                ->add('status', null, [
                    'label' => 'Statut',
                    'template' => 'Admin/show_status.html.twig'
                ])
                ->add('selected', null, ['label' => 'Sélectionné'])
                ->add('space', null, ['label' => 'Espace'])
                ->add('category', null, ['label' => "Type d'usage"])
                ->add('created', 'datetime', ['label' => 'Date de création', 'format' => 'd/m/Y à H:i'])
                ->add('updated', 'datetime', ['label' => 'Date de mise à jour', 'format' => 'd/m/Y à H:i'])
            ->end()
            ->with('Porteur de projet', ['class' => 'col-md-6'])
                ->add('projectHolder.fullName', null, ['label' => 'Nom complet'])
                ->add('projectHolder.email', null, ['label' => 'Email'])
                ->add('projectHolder.company', null, ['label' => 'Structure'])
                ->add('projectHolder.companyPhone', null, ['label' => 'Téléphone'])
                ->add('projectHolder.preferredDepartmentsLabelsForExport', null, ['label' => 'Départements souhaités'])
            ->end()
            ->with('Description du projet', ['class' => 'col-md-12'])
                ->add('description', TextType::class, ['label' => 'Description'])
                ->add('contribution', TextType::class, ['label' => 'Contribution au projet du propriétaire'])
                ->add('openToGlobalProject', null, ['label' => 'Ouvert au projet collectif'])
            ->end()
            ->with('Informations sur l\'occupation', ['class' => 'col-md-6'])
                ->add('projectHolder.companyStatus', null, ['label' => 'Statut juridique (profil)'])
                ->add('companyStatus', null, ['label' => 'Statut juridique (candidature)'])
                ->add('projectHolder.wishedSize', null, ['label' => 'Surface souhaitée (profil) (m²)'])
                ->add('wishedSize', null, ['label' => 'Surface souhaitée (candidature) (m²)'])
                ->add('fullLengthOccupation', null, ['label' => 'Durée d\'occupation'])
                ->add('startOccupation', 'date', ['label' => 'Date d\'entrée souhaitée', 'format' => 'd/m/Y'])
            ->end()
            ->with('Documents', ['class' => 'col-md-6'])
                ->add('files', null, ['label' => 'Fichiers joints', 'template' => 'Admin/show_files.html.twig'])
            ->end()
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name', null, [
                'label' => 'Nom du projet',
                'route' => ['name' => 'edit'],
            ])
            ->add('space', null, ['label' => 'Espace'])
            ->add('category', null, ['label' => "Type d'usage"])
            ->add('projectHolder', null, [
                'label' => 'Porteur de projet',
                'admin_code' => 'app.admin.project_holder',
                'associated_property' => 'email',
            ])
            ->add('created', 'datetime', ['label' => 'Date de création', 'format' => 'd/m/Y H:i'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Actions',
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }
    
    /**
     * Configuration des actions batch
    protected function configureBatchActions(array $actions): array
    {
        // Conserver l'action de suppression par défaut
        if (isset($actions['delete'])) {
            $actions['delete'] = $actions['delete'];
        }
        
        return $actions;
    }
    /**
     * Configure les actions disponibles sur la liste
     */
    protected function configureActionButtons(array $buttonList, string $action, ?object $object = null): array
    {
        $buttonList = parent::configureActionButtons($buttonList, $action, $object);
        
        if ($action === 'list') {
            $buttonList['export_custom'] = [
                'template' => 'Admin/button_export_custom.html.twig',
            ];
            $buttonList['statistics'] = [
                'template' => 'Admin/Application/button_statistics.html.twig',
            ];
        }
        
        return $buttonList;
    }
    
    protected function configureDashboardActions(array $actions): array
    {
        // Ajouter le bouton d'export personnalisé
        $actions['custom_export'] = [
            'label'              => 'Export personnalisé',
            'translation_domain' => 'SonataAdminBundle',
            'url'                => $this->generateUrl('select_export_fields'),
            'icon'               => 'fa fa-download',
        ];

        return $actions;
    }

    

    /** @return array<string, string> */
    public function getCustomExportFields(): array {
        return [
          'Espace' => 'space',
          'Statut' => 'statusLabel',
          'Nom' => 'name',
          'Structure' => 'projectHolder.company',
          'Nom du porteur' => 'projectHolder.fullName',
          'Téléphone' => 'projectHolder.companyPhone',
          'Email' => 'projectHolder.email',
          'Présentation' => 'projectHolder.companyDescription',
          'Facebook' => 'projectHolder.facebookUrl',
          'Twitter' => 'projectHolder.twitterUrl',
          'Instagram' => 'projectHolder.instagramUrl',
          'Linkedin' => 'projectHolder.linkedinUrl',
          'YouTube' => 'projectHolder.youtubeUrl',
          'TikTok' => 'projectHolder.tiktokUrl',
          'Bluesky' => 'projectHolder.googleUrl',
          'Autre' => 'projectHolder.otherUrl',
          'Description' => 'description',
          'Date de dépôt de la candidature' => 'created',
          'Type d\'usage' => 'category',
          'Statut juridique (profil)' => 'projectHolder.companyStatus',
          'Statut juridique (candidature)' => 'companyStatus',
          'Surface souhaitée (profil)' => 'projectHolder.wishedSize',
          'Surface souhaitée (candidature)' => 'wishedSize',
          'Durée d\'occupation souhaitée' => 'fullLengthOccupation',
          'Date d\'entrée souhaitée' => 'startOccupation',
          'Contribution au projet du propriétaire' => 'contribution'
        ];
    }

    
}
