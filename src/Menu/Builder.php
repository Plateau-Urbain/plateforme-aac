<?php
// vim:expandtab:sw=4 softtabstop=4:

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Builder
{
    private $tokenStorage;
    private $authorizationChecker;

    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        // security.context is deprecated in symfony 2.6
        // replace by 'security.token_storage' and 'security.authorization_checker'
        // https://symfony.com/blog/new-in-symfony-2-6-security-component-improvements
        $logged = $this->tokenStorage->getToken();

        $menu = $factory->createItem('root', ['childrenAttributes'=> ['class'=> 'nav navbar-nav',]]);

        // $Menu = $menu->addChild('La Coopérative', array('uri' => '#', 'attributes' => array('class'=>'dropdown menu-icon'), 'extras' => array(
        //    'safe_label' => true
        // ),'linkAttributes' => array('data-toggle' => 'dropdown', 'data-hover' => 'dropdown')));
        $Menu = $menu->addChild('La coopérative', ['uri' => '#', 'attributes' => ['class'=>'dropdown'], 'extras' => [
            'safe_label' => true
        ],'linkAttributes' => ['data-toggle' => 'dropdown', 'data-hover' => 'dropdown']])->setLabel('<span class="sub-arrow"><i class="fa fa-square"></i></span>La coopérative')->setExtra('safe_label',true);
	//
        $Menu->setChildrenAttribute('class', 'dropdown-menu');
        $Menu->addChild('Qui sommes-nous ?', ['uri' => 'https://www.plateau-urbain.com/la-cooperative-plateau-urbain/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Devenir sociétaire", ['uri' => 'https://www.plateau-urbain.com/la-cooperative-plateau-urbain/devenir-societaire/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Les équipes", ['uri' => 'https://www.plateau-urbain.com/la-cooperative-plateau-urbain/les-equipes/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Urbanisme transitoire", ['uri' => 'https://www.plateau-urbain.com/la-cooperative-plateau-urbain/urbanisme-transitoire/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Ressources", ['uri' => 'https://www.plateau-urbain.com/la-cooperative-plateau-urbain/ressources/', 'linkAttributes' => ['target' => '_top']]);
####
        $Menu = $menu->addChild('Notre offre', ['uri' => '#', 'attributes' => ['class'=>'dropdown'], 'extras' => [
            'safe_label' => true
       ],'linkAttributes' => ['data-toggle' => 'dropdown', 'data-hover' => 'dropdown']])->setLabel('<span class="sub-arrow"><i class="fa fa-square"></i></span>Notre offre')->setExtra('safe_label',true);
        $Menu->setChildrenAttribute('class', 'dropdown-menu');
        $Menu->addChild("Notre accompagnement", ['uri' => 'https://www.plateau-urbain.com/notre-accompagnement/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Montage et gestion", ['uri' => 'https://www.plateau-urbain.com/notre-accompagnement/montage-et-gestion/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Conseil et études", ['uri' => 'https://www.plateau-urbain.com/notre-accompagnement/conseil-et-etudes/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Privatisation", ['uri' => 'https://www.plateau-urbain.com/notre-accompagnement/privatisation/', 'linkAttributes' => ['target' => '_top']]);
        ####
        $Menu = $menu->addChild('Les tiers-lieux', ['uri' => '#', 'attributes' => ['class'=>'dropdown'], 'extras' => [
            'safe_label' => true
        ],'linkAttributes' => ['data-toggle' => 'dropdown', 'data-hover' => 'dropdown']])->setLabel('<span class="sub-arrow"><i class="fa fa-square"></i></span>Les tiers-lieux')->setExtra('safe_label',true);
        $Menu->setChildrenAttribute('class', 'dropdown-menu');
        $Menu->addChild("Définition", ['uri' => 'https://www.plateau-urbain.com/tiers-lieux/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Nos projets", ['uri' => 'https://www.plateau-urbain.com/tiers-lieux/nos-projets/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Actualités des lieux", ['uri' => 'https://www.plateau-urbain.com/tiers-lieux/actualites-des-lieux/', 'linkAttributes' => ['target' => '_top']]);
        $Menu->addChild("Occupant·es", ['uri' => 'https://www.plateau-urbain.com/tiers-lieux/occupant-es/', 'linkAttributes' => ['target' => '_top']]);
        $menu->addChild('Trouver un local', ['route' => 'search_index','attributes' => [
            'class' => 'local',
        ]])->setLabel('<span class="">Trouver un local</span>')->setExtra('safe_label',true);
        $menu->addChild('Mon compte', ['route' => 'app_login', 'attributes' => ['class'=>'pipe'], 'linkAttributes' => ['class' => 'connectMenu']]);
        ####
        if ($logged) {
            if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
                $user = $this->tokenStorage->getToken()->getUser();
                $context = $this->authorizationChecker;

/*            if ($user->isProprio() || $context->isGranted('ROLE_OWNER')) {
                $menu->addChild('Proposer un espace', array('route' => 'space_manager_add'));
}*/
/*            if ($user->isPorteur() || $context->isGranted('ROLE_PROJECT_HOLDER')) {
                $menu->addChild('Trouver un local', array('route' => 'search_index'));
}*/
                $loggedMenu = $menu->addChild('Mon compte', ['uri' => '#', 'attributes' => ['class'=>'dropdown pipe'], 'extras' => [
                    'safe_label' => true
                ],'linkAttributes' => ['data-toggle' => 'dropdown', 'data-hover' => 'dropdown','class' => 'connectMenured']]);
                $loggedMenu->setChildrenAttribute('class', 'dropdown-menu');

                $role = ($user->isProprio() || $context->isGranted('ROLE_OWNER')) ? "propriétaire" : "candidat";
                $role = $context->isGranted('ROLE_ADMIN') ? 'propriétaire' : $role;

                $loggedMenu->addChild('Mon profil '.$role, ['route' => 'security_profil', 'attributes' => ['class'=>'']]);

                if ($user->isProprio() || $context->isGranted('ROLE_OWNER')) {
                    $loggedMenu->addChild('Mes espaces', ['route' => 'space_manager_list', 'attributes' => ['class'=>'']]);
                    $loggedMenu->addChild('Ajouter un espace', ['route' => 'space_manager_add', 'attributes' => ['class'=>'']]);

                    /* $loggedMenu->addChild('Liste des AACs', ['route' => 'aac_list', 'attributes' => ['class' => '']]);*/
                } else if ($user->isPorteur() || $context->isGranted('ROLE_PROJECT_HOLDER')) {
                    $loggedMenu->addChild('Mes candidatures', ['route' => 'my_applications_list', 'attributes' => ['class'=>'']]);
                }

                $loggedMenu->addChild('Déconnexion', ['route' => 'app_logout', 'attributes' => ['class'=>'off-icon menu-icon']]);

            } else {

                #$menu->addChild('Proposer', array('route' => 'proprietaire'));
                /*$menu->addChild('Trouver un local', array('route' => 'search_index', 'attributes' => array('class'=>'local')));*/
                $menu->addChild('Trouver un local', ['route' => 'search_index','attributes' => [
                    'class' => 'local',
                ]])->setLabel('<span>Trouver un local</span>')->setExtra('safe_label',true);
                /*$menu['Trouver un local']->setLabel('<span class="sub-arrow"></span>')->setExtra('safe_label',true);*/
                $menu->addChild('Mon compte', ['route' => 'app_login', 'attributes' => ['class'=>'pipe'], 'linkAttributes' => ['class' => 'connectMenu']]);
            }
        }

       return $menu;
    }
}
