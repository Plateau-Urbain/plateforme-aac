<?php

namespace App\Admin;

use App\Entity\Application;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use App\Entity\Space;
use App\Entity\User;
use App\Form\UserDocumentType;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

/** @extends AbstractAdmin<User> */
class ProjectHolderAdmin extends AbstractAdmin
{
    protected $baseRouteName = 'project-holder';
    protected $baseRoutePattern = 'project-holder';

    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('show');
        $collection->add('select_export_fields', 'select-export-fields');
        $collection->add('custom_export', 'custom-export');
        $collection->add('help_filters', 'help-filters-export');
        $collection->add('statistics', 'statistics');
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        assert($query instanceof ProxyQuery);
        $alias = $query->getRootAliases()[0];
        $em = $query->getQueryBuilder()->getEntityManager();

        $ownerIdsDql = $em->createQueryBuilder()
            ->select('IDENTITY(sp.owner)')
            ->from(Space::class, 'sp')
            ->where('sp.owner IS NOT NULL')
            ->getDQL();

        $holderIdsDql = $em->createQueryBuilder()
            ->select('IDENTITY(a.projectHolder)')
            ->from(Application::class, 'a')
            ->where('a.projectHolder IS NOT NULL')
            ->getDQL();

        // Compat migration : type_user souvent NULL ; en PHP SF3 `null == 0` comptait
        // comme porteur. On inclut les liens candidature + les comptes non-propriétaires.
        $query->andWhere(
            $query->expr()->orX(
                $query->expr()->eq($alias.'.typeUser', ':typeUser'),
                $query->expr()->in($alias.'.id', $holderIdsDql),
                $query->expr()->andX(
                    $query->expr()->isNull($alias.'.typeUser'),
                    $query->expr()->notIn($alias.'.id', $ownerIdsDql),
                    $query->expr()->notLike($alias.'.roles', ':roleOwnerPattern'),
                    $query->expr()->notLike($alias.'.roles', ':roleAdminPattern'),
                    $query->expr()->notLike($alias.'.roles', ':roleSuperAdminPattern')
                )
            )
        );
        $query->setParameter('typeUser', User::PORTEUR);
        $query->setParameter('roleOwnerPattern', '%"ROLE_OWNER"%');
        $query->setParameter('roleAdminPattern', '%"ROLE_ADMIN"%');
        $query->setParameter('roleSuperAdminPattern', '%"ROLE_SUPER_ADMIN"%');

        return $query;
    }

    // setup the default sort column and order
    /** @var array<string, mixed> */
    protected array $datagridValues = [
        '_sort_order' => 'ASC',
        '_sort_by' => 'lastname',
    ];

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->with('General')
            ->add('email')
            ->add('plainPassword', TextType::class, [
                'required' => $this->getSubject()->getId() === null,
                'label'     => 'Mot de passe',
            ])
            ->add('enabled', ChoiceType::class, ['label' => 'Activé', 'choices' => ['Oui' => true, 'Non' => false]])
            ->end()
            ->with('Profile')
            ->add('civility', ChoiceType::class, ['choices' => User::getAllCivilities(), 'required' => false, 'label' => 'Civilité'])
            ->add('firstname', null, ['required' => false, 'label' => 'Prénom'])
            ->add('lastname', null, ['required' => false, 'label' => 'Nom'])
            ->add('birthday', BirthdayType::class, ['required' => false, 'label' => 'Date de naissance'])
            ->add('phone', null, ['required' => false, 'label' => 'Téléphone'])
            ->add('description', null, ['required' => false, 'label' => 'Description'])
            ->add('newsletter', ChoiceType::class, ['required' => false, 'label' => 'Souhaite recevoir la newsletter', 'choices' => ['Oui' => true, 'Non' => false]])

            ->end()
            ->with('Structure')
            ->add('company', null, ['required' => false, 'label' => 'Structure'])
            ->add('companyStatus', ChoiceType::class, ['choices' => User::getAllCompanyStatut(), 'required' => false, 'label' => 'Statut'])
            ->add('companyCreationDate', BirthdayType::class, ['required' => false, 'label' => 'Date de création'])
            ->add('siret', null, ['required' => false, 'label' => 'SIRET'])
            ->add('address', null, ['required' => false, 'label' => 'Adresse Structure'])
            ->add('addressSuite', null, ['required' => false, 'label' => 'Adresse Structure (suite)'])
            ->add('zipcode', null, ['required' => false, 'label' => 'Code Postal Structure'])
            ->add('city', null, ['required' => false, 'label' => 'Ville Structure'])
            ->add('companyPhone', null, ['required' => false, 'label' => 'Téléphone fixe'])
            ->add('companyMobile', null, ['required' => false, 'label' => 'Téléphone mobile'])
            ->add('companyDescription', null, ['required' => false, 'label' => 'Description'])
            ->add('companyEffective', null, ['required' => false, 'label' => 'Nombre de personnes dans la structure'])
            ->add('companyStructures', null, ['required' => false, 'label' => 'Structure(s) d\'accompagnement'])
            ->add('company_site', null, ['required' => false, 'label' => 'Site web'])
            ->add('company_blog', null, ['required' => false, 'label' => 'Blog'])

            ->end()
            ->with('Souhaits')
            ->add('wishedSize', null, ['required' => false, 'label' => 'Surface souhaitée (m²)'])
            ->add('preferredDepartments', ChoiceType::class, [
                'label' => 'Départements souhaités',
                'choices' => User::getAllFrenchDepartments(),
                'multiple' => true,
                'required' => false,
            ])
            ->add('useType', null, ['required' => false, 'label' => 'Type de projet'])
            ->add('usageDate', null, ['required' => false, 'label' => 'Date de disponibilité'])
            ->add('usageDuration', null, ['required' => false, 'label' => 'Durée d\'occupation'])
            ->add('lengthTypeOccupation', ChoiceType::class, ['choices' => Application::getAllLengthType(), 'required' => false, 'label' => 'Type de durée'])
            ->add('projectDescription', null, ['required' => false, 'label' => 'Présentation du projet'])

            ->end()
            ->with('Réseaux sociaux')
            ->add('facebookUrl', null, ['required' => false, 'label' => 'Facebook'])
            ->add('twitterUrl', null, ['required' => false, 'label' => 'Twitter / X'])
            ->add('instagramUrl', null, ['required' => false, 'label' => 'Instagram'])
            ->add('linkedinUrl', null, ['required' => false, 'label' => 'LinkedIn'])
            ->add('youtubeUrl', null, ['required' => false, 'label' => 'YouTube'])
            ->add('tiktokUrl', null, ['required' => false, 'label' => 'TikTok'])
            ->add('googleUrl', null, ['required' => false, 'label' => 'Bluesky'])
            ->add('otherUrl', null, ['required' => false, 'label' => 'Autre URL'])

            ->end()

            ->with('Documents')
            ->add('documents', CollectionType::class, [
                'entry_type' => UserDocumentType::class,
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
            ->add('email', null, ['label' => 'Email'])
            ->add('firstname', null, ['label' => 'Prénom'])
            ->add('lastname', null, ['label' => 'Nom'])
            ->add('company', null, ['label' => 'Structure'])
            ->add('companyStatus', ChoiceFilter::class, [
                'label' => 'Statut juridique',
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => User::getAllCompanyStatut(),
                ],
            ])
            ->add('zipcode', null, ['label' => 'Code postal'])
            ->add('city', null, ['label' => 'Ville'])
            ->add('preferredDepartments', CallbackFilter::class, [
                'label' => 'Département souhaité',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue() || $data->getValue() === null || $data->getValue() === '') {
                        return false;
                    }

                    $val = $data->getValue();
                    $qb = $query->getQueryBuilder();
                    
                    $parameterName = 'prefDept_' . uniqid();
                    $qb->andWhere(sprintf("CONCAT(',', CONCAT(%s.preferredDepartments, ',')) LIKE :%s", $alias, $parameterName))
                       ->setParameter($parameterName, '%,' . $val . ',%');

                    return true;
                },
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => User::getAllFrenchDepartments(),
                    'placeholder' => 'Sélectionner un département...',
                ]
            ])
            ->add('useType', null, ['label' => 'Type de projet'])
            ->add('wishedSizeMin', CallbackFilter::class, [
                'label' => 'Surface souhaitée min (m²)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue() || $data->getValue() === null || $data->getValue() === '') {
                        return false;
                    }

                    $query->getQueryBuilder()
                        ->andWhere(sprintf('%s.wishedSize >= :wishedSizeMin', $alias))
                        ->setParameter('wishedSizeMin', $data->getValue());

                    return true;
                },
                'field_type' => NumberType::class,
            ])
            ->add('wishedSizeMax', CallbackFilter::class, [
                'label' => 'Surface souhaitée max (m²)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue() || $data->getValue() === null || $data->getValue() === '') {
                        return false;
                    }

                    $query->getQueryBuilder()
                        ->andWhere(sprintf('%s.wishedSize <= :wishedSizeMax', $alias))
                        ->setParameter('wishedSizeMax', $data->getValue());

                    return true;
                },
                'field_type' => NumberType::class,
            ])
            ->add('usageDate', DateRangeFilter::class, ['label' => 'Date de disponibilité'])
            ->add('createdAt', DateTimeRangeFilter::class, ['label' => 'Date d\'inscription'])
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('email', null, [
                'route' => ['name' => 'edit'],
            ])
            ->add('firstname', null, ['label' => 'Prénom'])
            ->add('lastname', null, ['label' => 'Nom'])
            ->add('company', null, ['label' => 'Structure'])
            ->add('wishedSize', null, ['label' => 'Surface (m²)', 'row_align' => 'left', 'header_style' => 'text-align: left'])
            ->add('preferredDepartmentsLabelsForExport', null, ['label' => 'Départements souhaités'])
            ->add('usageDate', 'date', ['label' => 'Disponibilité', 'format' => 'd/m/Y'])
            ->add('createdAt', 'datetime', ['label' => 'Date d\'inscription', 'format' => 'd/m/Y H:i'])
            ->add('enabled', null, ['label' => 'Activé'])
            ->add('locked', null, ['label' => 'Verrouillé'])
        ;
    }

    
    protected function alterNewInstance(object $object): void
    {
        $object->setTypeUser(User::PORTEUR);
    }


    /** @param iterable<\App\Entity\UserDocument> $children */
    public function syncDocs(\App\Entity\User $user, iterable $children): void
    {
        foreach ($children as $child) {
            $child->setProjectHolder($user);
        }
    }

    public function prePersist(object $object): void
    {
        $this->preUpdate($object);
    }

    public function preUpdate(object $object): void
    {
        $object->setEmailCanonical(strtolower($object->getEmail()));
        if ($object->getPlainPassword()) {
            $object->setPassword($this->passwordHasher->hashPassword($object, $object->getPlainPassword()));
            $object->setPlainPassword(null);
        }

        $this->syncDocs($object, $object->getDocuments());
    }

    protected function configureActionButtons(array $buttonList, string $action, ?object $object = null): array
    {
        $buttonList = parent::configureActionButtons($buttonList, $action, $object);
        
        if ($action === 'list') {
            $buttonList['export_custom'] = [
                'template' => 'Admin/button_export_custom.html.twig',
            ];
            $buttonList['statistics'] = [
                'template' => 'Admin/User/button_statistics.html.twig',
            ];
        }
        
        return $buttonList;
    }
    
    protected function configureDashboardActions(array $actions): array
    {
        $actions['custom_export'] = [
            'label'              => 'Export personnalisé',
            'translation_domain' => 'SonataAdminBundle',
            'url'                => $this->generateUrl('select_export_fields'),
            'icon'               => 'fa fa-download',
        ];

        return $actions;
    }

    
}
