<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Space;
use App\Entity\User;

/** @extends AbstractAdmin<User> */
class OwnerAdmin extends AbstractAdmin
{
    protected $baseRouteName = 'owner';
    protected $baseRoutePattern = 'owner';

    private const SORT_CHOICES = [
        'Structure (A → Z)' => 'company_asc',
        'Structure (Z → A)' => 'company_desc',
        'Email (A → Z)' => 'email_asc',
        'Email (Z → A)' => 'email_desc',
        'Nom (A → Z)' => 'lastname_asc',
        'Nom (Z → A)' => 'lastname_desc',
    ];

    /** @var array<string, array{0: string, 1: string}> */
    private const SORT_MAP = [
        'company_asc' => ['company', 'ASC'],
        'company_desc' => ['company', 'DESC'],
        'email_asc' => ['email', 'ASC'],
        'email_desc' => ['email', 'DESC'],
        'lastname_asc' => ['lastname', 'ASC'],
        'lastname_desc' => ['lastname', 'DESC'],
    ];

    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_BY] = 'company';
        $sortValues[DatagridInterface::SORT_ORDER] = 'ASC';
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    protected function configureFilterParameters(array $parameters): array
    {
        $sortValue = $parameters['sortBy']['value'] ?? null;
        if (\is_string($sortValue) && isset(self::SORT_MAP[$sortValue])) {
            [$sortBy, $sortOrder] = self::SORT_MAP[$sortValue];
            $parameters[DatagridInterface::SORT_BY] = $sortBy;
            $parameters[DatagridInterface::SORT_ORDER] = $sortOrder;
        }

        return $parameters;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('show');
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

        // Compat migration : type_user souvent NULL en base, les propriétaires
        // sont identifiés via space.owner_id et/ou ROLE_OWNER (comme en SF 3.4).
        $query->andWhere(
            $query->expr()->orX(
                $query->expr()->eq($alias.'.typeUser', ':typeUser'),
                $query->expr()->in($alias.'.id', $ownerIdsDql),
                $query->expr()->like($alias.'.roles', ':roleOwnerPattern')
            )
        );
        $query->setParameter('typeUser', User::PROPRIO);
        $query->setParameter('roleOwnerPattern', '%"ROLE_OWNER"%');

        return $query;
    }

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
            ->add('enabled', ChoiceType::class, [
                'label' => 'Activé',
                'required' => false,
                'choices' => ['Oui' => true, 'Non' => false],
                'help' => 'Mettre « Oui » pour activer manuellement un compte en attente de confirmation e-mail.',
            ])

            ->end()
            ->with('Profile')

            ->add('civility', ChoiceType::class, ['choices' => User::getAllCivilities(), 'required' => false, 'label' => 'Civilité'])
            ->add('firstname', null, ['required' => false, 'label' => 'Prénom'])
            ->add('lastname', null, ['required' => false, 'label' => 'Nom'])
            ->add('companyFunction', null, ['required' => false, 'label' => 'Fonction'])
            ->add('companyPhone', null, ['required' => false, 'label' => 'Téléphone'])
            ->add('companyMobile', null, ['required' => false, 'label' => 'Téléphone mobile'])

            ->end()
            ->with('Structure')

            ->add('company', null, ['required' => false, 'label' => 'Structure'])
            ->add('companyStatus', ChoiceType::class, ['choices' => User::getAllProCompanyStatut(), 'required' => false, 'label' => 'Statut'])
            ->add('address', null, ['required' => false, 'label' => 'Adresse'])
            ->add('addressSuite', null, ['required' => false, 'label' => 'Adresse (suite)'])
            ->add('zipcode', null, ['required' => false, 'label' => 'Code Postal'])
            ->add('city', null, ['required' => false, 'label' => 'Ville Structure'])
            ->add('company_site', null, ['required' => false, 'label' => 'Site Web'])

            ->end()

        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('sortBy', CallbackFilter::class, [
                'label' => 'Trier par',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    return $data->hasValue() && $data->getValue() !== null && $data->getValue() !== '';
                },
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => self::SORT_CHOICES,
                    'placeholder' => 'Choisir',
                ],
            ])
            ->add('company', null, ['label' => 'Structure'])
            ->add('email', null, ['label' => 'Email'])
            ->add('lastname', null, ['label' => 'Nom'])
            ->add('firstname', null, ['label' => 'Prénom'])
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('email', null, [
                'label' => 'Email',
                'route' => ['name' => 'edit'],
            ])
            ->add('company', null, ['label' => 'Structure'])
            ->add('firstname', null, ['label' => 'Prénom'])
            ->add('lastname', null, ['label' => 'Nom'])
            ->add('enabled', null, [
                'label' => 'Activé',
                'editable' => true,
            ])
            ->add('locked', null, ['label' => 'Verrouillé'])
        ;
    }

    
    protected function alterNewInstance(object $object): void
    {
        $object->setTypeUser(User::PROPRIO);
    }

    
    public function preUpdate(object $object): void
    {
        $object->setTypeUser(User::PROPRIO);
        $object->addRole('ROLE_OWNER');
        $object->setEmailCanonical(strtolower($object->getEmail()));
        if ($object->getPlainPassword()) {
            $object->setPassword($this->passwordHasher->hashPassword($object, $object->getPlainPassword()));
            $object->setPlainPassword(null);
        }
        if ($object->isEnabled()) {
            $object->setConfirmationToken(null);
        }
    }

    public function prePersist(object $object): void
    {
        $object->setTypeUser(User::PROPRIO);
        $object->addRole('ROLE_OWNER');

        // Synchroniser l'email canonique
        $object->setEmailCanonical(strtolower((string) $object->getEmail()));

        // Synchroniser username / usernameCanonical (colonnes legacy FOS)
        if (!$object->getUsername()) {
            $object->setUsername($object->getEmail());
        }
        if (!$object->getUsernameCanonical()) {
            $object->setUsernameCanonical(strtolower((string) $object->getEmail()));
        }

        // Hasher le mot de passe (colonne NOT NULL en base)
        if ($object->getPlainPassword()) {
            $object->setPassword(
                $this->passwordHasher->hashPassword($object, $object->getPlainPassword())
            );
            $object->setPlainPassword(null);
        } elseif (!$object->getPassword()) {
            // Sécurité : mot de passe vide → chaîne impossible à utiliser
            $object->setPassword('!');
        }
        if ($object->isEnabled()) {
            $object->setConfirmationToken(null);
        }
    }

    
}
