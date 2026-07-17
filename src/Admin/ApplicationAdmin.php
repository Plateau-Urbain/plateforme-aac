<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\Type\CollectionType;
use App\Form\ApplicationFileType;
use App\Form\Admin\ApplicationLocationPreferenceFilterType;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\Application;
use App\Entity\ApplicationLocationPreference;
use App\Entity\Space;
use App\Entity\SpaceLocation;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/** @extends AbstractAdmin<Application> */
class ApplicationAdmin extends AbstractAdmin
{
    protected $baseRouteName = 'candidature';
    protected $baseRoutePattern = 'candidature';

    public function __construct(
        private EntityManagerInterface $em,
        ?string $code = null,
        ?string $class = null,
        ?string $baseControllerName = null,
    ) {
        parent::__construct($code, $class, $baseControllerName);
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'created';
    }

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
        $siteChoices = $this->getMultiLocationSiteFilterChoices();
        $rankChoices = $this->getLocationPreferenceRankChoices();

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
            ->add('locationPreference', CallbackFilter::class, [
                'label' => 'Choix de site (AAC multi-sites)',
                'callback' => function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }

                    $value = $data->getValue();
                    if (!\is_array($value)) {
                        return false;
                    }

                    $siteId = $value['site'] ?? null;
                    if ($siteId === null || $siteId === '') {
                        return false;
                    }

                    $dql = sprintf(
                        'SELECT 1 FROM %s lp_filter WHERE lp_filter.application = %s AND lp_filter.location = :locationPrefSite',
                        ApplicationLocationPreference::class,
                        $alias
                    );

                    $qb = $query->getQueryBuilder();
                    $qb->setParameter('locationPrefSite', (int) $siteId);

                    $rank = $value['rank'] ?? null;
                    if ($rank !== null && $rank !== '') {
                        $dql .= ' AND lp_filter.rank = :locationPrefRank';
                        $qb->setParameter('locationPrefRank', (int) $rank);
                    }

                    $qb->andWhere($qb->expr()->exists($dql));

                    return true;
                },
                'field_type' => ApplicationLocationPreferenceFilterType::class,
                'field_options' => [
                    'site_choices' => $siteChoices,
                    'rank_choices' => $rankChoices,
                ],
            ])
            ->add('created', DateTimeRangeFilter::class, ['label' => 'Date de création'])
            ->add('startOccupation', DateRangeFilter::class, ['label' => 'Date d\'entrée souhaitée'])
            ->add('companyStatus', null, ['label' => 'Statut juridique (candidature)'])
            ->add('wishedSizeMin', CallbackFilter::class, [
                'label' => 'Surface souhaitée min (candidature) (m²)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue() || $data->getValue() === null || $data->getValue() === '') {
                        return false;
                    }

                    $query->getQueryBuilder()
                        ->andWhere(sprintf('%s.wishedSize >= :applicationWishedSizeMin', $alias))
                        ->setParameter('applicationWishedSizeMin', $data->getValue());

                    return true;
                },
                'field_type' => NumberType::class,
            ])
            ->add('wishedSizeMax', CallbackFilter::class, [
                'label' => 'Surface souhaitée max (candidature) (m²)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue() || $data->getValue() === null || $data->getValue() === '') {
                        return false;
                    }

                    $query->getQueryBuilder()
                        ->andWhere(sprintf('%s.wishedSize <= :applicationWishedSizeMax', $alias))
                        ->setParameter('applicationWishedSizeMax', $data->getValue());

                    return true;
                },
                'field_type' => NumberType::class,
            ])
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
            ->add('locationPreferencesLabelsForExport', null, [
                'label' => 'Choix de sites',
                'sortable' => false,
            ])
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

    /** @return array<string, string> */
    private function getMultiLocationSiteFilterChoices(): array
    {
        /** @var SpaceLocation[] $locations */
        $locations = $this->em->createQueryBuilder()
            ->select('loc', 'space')
            ->from(SpaceLocation::class, 'loc')
            ->innerJoin('loc.space', 'space')
            ->where('space.workflowType = :workflow')
            ->setParameter('workflow', Space::WORKFLOW_MULTI_LOCATION)
            ->orderBy('space.name', 'ASC')
            ->addOrderBy('loc.displayOrder', 'ASC')
            ->addOrderBy('loc.id', 'ASC')
            ->getQuery()
            ->getResult();

        $choices = [];
        foreach ($locations as $location) {
            $space = $location->getSpace();
            $label = (string) $location->getName();
            if ($location->getCity()) {
                $label .= ' (' . $location->getCity() . ')';
            }
            if ($space && $space->getName()) {
                $label = $space->getName() . ' — ' . $label;
            }

            $choices[$label] = (string) $location->getId();
        }

        return $choices;
    }

    /** @return array<string, string> */
    private function getLocationPreferenceRankChoices(): array
    {
        $maxRank = (int) $this->em->createQueryBuilder()
            ->select('MAX(lp.rank)')
            ->from(ApplicationLocationPreference::class, 'lp')
            ->getQuery()
            ->getSingleScalarResult();

        if ($maxRank < 1) {
            $maxRank = 10;
        }

        $choices = [];
        for ($rank = 1; $rank <= $maxRank; ++$rank) {
            $choices[$this->formatLocationPreferenceRankLabel($rank)] = (string) $rank;
        }

        return $choices;
    }

    private function formatLocationPreferenceRankLabel(int $rank): string
    {
        return match ($rank) {
            1 => '1er choix',
            2 => '2e choix',
            default => $rank . 'e choix',
        };
    }

    
}
