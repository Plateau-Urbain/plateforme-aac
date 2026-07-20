<?php

namespace App\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/** @extends AbstractAdmin<User> */
class UserAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'utilisateurs';
    protected $baseRouteName = 'utilisateurs';

    public function __construct(private \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('impersonate', $this->getRouterIdParameter() . '/impersonate');
        $collection->add('select_export_fields', 'select-export-fields');
        $collection->add('custom_export', 'custom-export');
    }

    public function getExportFormats(): array
    {
        return ['csv', 'xls'];
    }

    /** @return array<string, array{label: string, property: string, category: string}> */
    public static function getExportFieldDefinitions(): array
    {
        return [
            'id' => [
                'label' => 'Identifiant',
                'property' => 'id',
                'category' => 'Informations personnelles',
            ],
            'email' => [
                'label' => 'Email',
                'property' => 'email',
                'category' => 'Informations personnelles',
            ],
            'civility' => [
                'label' => 'Civilité',
                'property' => 'civility',
                'category' => 'Informations personnelles',
            ],
            'firstname' => [
                'label' => 'Prénom',
                'property' => 'firstname',
                'category' => 'Informations personnelles',
            ],
            'lastname' => [
                'label' => 'Nom',
                'property' => 'lastname',
                'category' => 'Informations personnelles',
            ],
            'fullname' => [
                'label' => 'Nom complet',
                'property' => 'fullname',
                'category' => 'Informations personnelles',
            ],
            'birthday' => [
                'label' => 'Date de naissance',
                'property' => 'birthday',
                'category' => 'Informations personnelles',
            ],
            'phone' => [
                'label' => 'Téléphone',
                'property' => 'phone',
                'category' => 'Informations personnelles',
            ],
            'typeUser' => [
                'label' => 'Type d\'utilisateur',
                'property' => 'computed.typeUserLabel',
                'category' => 'Compte',
            ],
            'roles' => [
                'label' => 'Rôles',
                'property' => 'computed.rolesLabel',
                'category' => 'Compte',
            ],
            'enabled' => [
                'label' => 'Activé',
                'property' => 'computed.yesno.enabled',
                'category' => 'Compte',
            ],
            'locked' => [
                'label' => 'Verrouillé',
                'property' => 'computed.yesno.locked',
                'category' => 'Compte',
            ],
            'createdAt' => [
                'label' => 'Date de création',
                'property' => 'createdAt',
                'category' => 'Compte',
            ],
            'newsletter' => [
                'label' => 'Newsletter',
                'property' => 'computed.yesno.newsletter',
                'category' => 'Préférences',
            ],
            'preferredDepartments' => [
                'label' => 'Départements souhaités',
                'property' => 'preferredDepartmentsLabelsForExport',
                'category' => 'Préférences',
            ],
            'company' => [
                'label' => 'Nom de la structure',
                'property' => 'company',
                'category' => 'Structure',
            ],
            'companyStatus' => [
                'label' => 'Statut juridique',
                'property' => 'companyStatus',
                'category' => 'Structure',
            ],
            'siret' => [
                'label' => 'SIRET',
                'property' => 'siret',
                'category' => 'Structure',
            ],
            'address' => [
                'label' => 'Adresse',
                'property' => 'address',
                'category' => 'Structure',
            ],
            'zipcode' => [
                'label' => 'Code postal',
                'property' => 'zipcode',
                'category' => 'Structure',
            ],
            'city' => [
                'label' => 'Ville',
                'property' => 'city',
                'category' => 'Structure',
            ],
            'companyPhone' => [
                'label' => 'Téléphone structure',
                'property' => 'companyPhone',
                'category' => 'Structure',
            ],
            'companyMobile' => [
                'label' => 'Mobile structure',
                'property' => 'companyMobile',
                'category' => 'Structure',
            ],
        ];
    }

    protected function configureExportFields(): array
    {
        $fields = [];
        foreach (self::getExportFieldDefinitions() as $definition) {
            $fields[$definition['label']] = self::mapExportPropertyForStandardExport($definition['property']);
        }

        return $fields;
    }

    private static function mapExportPropertyForStandardExport(string $property): string
    {
        return match ($property) {
            'computed.typeUserLabel' => 'typeUser',
            'computed.rolesLabel' => 'roles',
            'computed.yesno.enabled' => 'enabled',
            'computed.yesno.locked' => 'locked',
            'computed.yesno.newsletter' => 'newsletter',
            default => $property,
        };
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'createdAt';
    }

    protected function configureActionButtons(array $buttonList, string $action, ?object $object = null): array
    {
        $buttonList = parent::configureActionButtons($buttonList, $action, $object);

        if ($action === 'list') {
            $buttonList['export_custom'] = [
                'template' => 'Admin/button_export_custom.html.twig',
            ];
        }

        return $buttonList;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('Identité')
                ->with('Informations personnelles', ['class' => 'col-md-6'])
                    ->add('civility', ChoiceType::class, [
                        'label'       => 'Civilité',
                        'choices'     => ['M.' => User::MISTER, 'Mme' => User::MISS, 'Autre' => User::AUTRE],
                        'required'    => false,
                        'placeholder' => '-- Choisir --',
                    ])
                    ->add('firstname', TextType::class, ['label' => 'Prénom', 'required' => false])
                    ->add('lastname', TextType::class, ['label' => 'Nom', 'required' => false])
                    ->add('email', EmailType::class, ['label' => 'Email'])
                    ->add('plainPassword', TextType::class, [
                        'required' => $this->getSubject()->getId() === null,
                        'label'     => 'Mot de passe',
                    ])
                    ->add('birthday', DateType::class, [
                        'label'    => 'Date de naissance',
                        'widget'   => 'single_text',
                        'required' => false,
                    ])
                ->end()
                ->with('Rôles', ['class' => 'col-md-6'])
                    ->add('typeUser', ChoiceType::class, [
                        'label'   => 'Type d\'utilisateur',
                        'choices' => [
                            'Porteur de projet' => User::PORTEUR,
                            'Propriétaire'      => User::PROPRIO,
                        ],
                        'required' => false,
                    ])
                    ->add('preferredDepartments', ChoiceType::class, [
                        'label'    => 'Départements souhaités',
                        'choices'  => User::getAllFrenchDepartments(),
                        'multiple' => true,
                        'required' => false,
                    ])
                    ->add('enabled', null, [
                        'label' => 'Activé',
                        'required' => false,
                        'help' => 'Décochez pour bloquer la connexion. Cochez pour activer manuellement un compte en attente d’e-mail de confirmation.',
                    ])
                ->end()
            ->end()
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('email', null, ['label' => 'Email'])
            ->add('firstname', null, ['label' => 'Prénom'])
            ->add('lastname', null, ['label' => 'Nom'])
            ->add('typeUser', ChoiceType::class, [
                'label'   => 'Type',
                'choices' => [
                    User::PORTEUR => 'Porteur',
                    User::PROPRIO => 'Propriétaire',
                    User::ADMIN   => 'Administrateur',
                ],
            ])
            ->add('enabled', null, [
                'label' => 'Actif',
                'editable' => true,
            ])
            ->add('createdAt', 'datetime', ['label' => 'Créé le', 'format' => 'd/m/Y H:i'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show'        => [],
                    'edit'        => [],
                    'impersonate' => ['template' => 'Admin/User/list__action_impersonate.html.twig'],
                    'delete'      => [],
                ],
            ])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('email', null, ['label' => 'Email'])
            ->add('firstname', null, ['label' => 'Prénom'])
            ->add('lastname', null, ['label' => 'Nom'])
            ->add('enabled', null, ['label' => 'Actif'])
            ->add('createdAt', DateTimeRangeFilter::class, ['label' => 'Créé le'])
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('Identité')
                ->add('civility', null, ['label' => 'Civilité'])
                ->add('firstname', null, ['label' => 'Prénom'])
                ->add('lastname', null, ['label' => 'Nom'])
                ->add('email', null, ['label' => 'Email'])
                ->add('birthday', null, ['label' => 'Date de naissance'])
                ->add('typeUser', null, ['label' => 'Type d\'utilisateur'])
                ->add('preferredDepartmentsLabelsForExport', null, ['label' => 'Départements souhaités'])
                ->add('enabled', null, ['label' => 'Actif'])
                ->add('createdAt', null, ['label' => 'Créé le'])
            ->end()
        ;
    }

    public function prePersist(object $object): void
    {
        if ($object instanceof User) {
            if ($object->getCreatedAt() === null) {
                $object->setCreatedAt(new \DateTime());
            }
            $this->hashPassword($object);
            $this->syncActivationState($object);
        }
    }

    public function preUpdate(object $object): void
    {
        if ($object instanceof User) {
            $this->hashPassword($object);
            $this->syncActivationState($object);
        }
    }

    private function syncActivationState(User $user): void
    {
        if ($user->isEnabled()) {
            $user->setConfirmationToken(null);
        }
    }

    private function hashPassword(User $user): void
    {
        if ($user->getPlainPassword()) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPlainPassword()));
            $user->setPlainPassword(null);
        }
    }
}
