<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
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

    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
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
            ->add('enabled', ChoiceType::class, ['label' => 'Activé', 'required' => false, 'choices' => ['Oui' => true, 'Non' => false]])

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
            ->add('email')
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
            ->add('lastName', null, ['label' => 'Nom'])
            ->add('enabled', null, ['label' => 'Activé'])
            ->add('locked', null, ['label' => 'Verouillé'])
        ;
    }

    
    protected function alterNewInstance(object $object): void
    {
        $object->setTypeUser(User::PROPRIO);
    }

    
    public function preUpdate(object $object): void
    {
        $object->setEmailCanonical(strtolower($object->getEmail()));
        if ($object->getPlainPassword()) {
            $object->setPassword($this->passwordHasher->hashPassword($object, $object->getPlainPassword()));
            $object->setPlainPassword(null);
        }
    }

    public function prePersist(object $object): void {
        $object->addRole('ROLE_OWNER');
    }

    
}
