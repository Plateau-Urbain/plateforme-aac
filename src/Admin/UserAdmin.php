<?php

namespace App\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
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

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('impersonate', $this->getRouterIdParameter() . '/impersonate');
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
                    ->add('enabled', null, ['label' => 'Activé', 'required' => false])
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
            ->add('enabled', null, ['label' => 'Actif'])
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
        if ($object instanceof User && $object->getCreatedAt() === null) {
            $object->setCreatedAt(new \DateTime());
        }
    }
}
