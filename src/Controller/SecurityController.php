<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\User;
use App\Entity\Application;
use App\Entity\UserDocument;
use App\Form\ProjectOwnerType;
use App\Form\SpaceOwnerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;

class SecurityController extends AbstractController
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private FormFactoryInterface $formFactory;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, FormFactoryInterface $formFactory)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->formFactory = $formFactory;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('homepage');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/profil', name: 'security_profil')]
    #[Route(path: '/inscription/confirmation', name: 'fos_user_registration_confirmed')]
    public function inscriptionConfirmationAction(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        assert($user instanceof User);

        $next = $request->get('next');
        if (!is_string($next)) {
            $next = null;
        }
        $returnAnchor = $request->get('return_anchor');
        if (!is_string($returnAnchor) || $returnAnchor === '') {
            $returnAnchor = null;
        }

        return $this->redirect($this->generateUrl('security_profil_role', [
            'role' => $user->isProprio() ? 'proprio' : 'candidat',
            'next' => $next
        ]));
    }

    #[Route(path: '/profil/{role}', name: 'security_profil_role')]
    public function profilAction(Request $request, string $role, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        assert($user instanceof User);

        $next = $request->get('next');
        if (!is_string($next)) {
            $next = null;
        }
        $returnAnchor = $request->get('return_anchor');
        if (!is_string($returnAnchor) || $returnAnchor === '') {
            $returnAnchor = null;
        }

        $this->logger->debug('profilAction() user '.$user->getId().' '.$user->getUserIdentifier().' '.($user->isProprio() ? 'PROPRIO' : ''));

        if ($user->isProprio() && $role === 'proprio') {
            $form = $this->createForm(SpaceOwnerType::class, $user);
            $template = 'Security/profilProprio.html.twig';
        } elseif ($role === 'candidat') {
            $form = $this->createForm(ProjectOwnerType::class, $user, ['noPlainPassword' => true]);
            $template = 'Security/profil.html.twig';
        } else {
            throw new AccessDeniedException();
        }

        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $oldPwdRaw = $user->isProprio() && $form->get('userInfo')->has('oldPassword') ? $form->get('userInfo')->get('oldPassword')->getData() : null;
            $old_pwd = is_string($oldPwdRaw) ? $oldPwdRaw : '';
            $newPwdRaw = $user->isProprio() && $form->get('userInfo')->has('plainPassword') ? $form->get('userInfo')->get('plainPassword')->getData() : null;
            $new_pwd = is_string($newPwdRaw) ? $newPwdRaw : '';

            if (empty($old_pwd) || empty($new_pwd)) {
                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash('success_msg', "Profil mis à jour");

                if ($next && $this->isSafeRedirectUrl($next)) {
                    if ($returnAnchor) {
                        return $this->redirect($next . '#' . $returnAnchor);
                    }
                    return $this->redirect($next);
                }

                return $this->redirect($this->generateUrl('security_profil_role', [
                    'role' => $role,
                    'next' => $next,
                    'return_anchor' => $returnAnchor,
                ]));
            }

            if (!$passwordHasher->isPasswordValid($user, $old_pwd)) {
                $this->addFlash('error_msg', "Erreur dans le mot de passe actuel");
                return $this->redirect($this->generateUrl('security_profil_role', [
                    'role' => $role,
                    'next' => $next,
                    'return_anchor' => $returnAnchor,
                ]));
            }

            $new_pwd_encoded = $passwordHasher->hashPassword($user, $new_pwd);
            $user->setPassword($new_pwd_encoded);
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success_msg', "Profil mis à jour");

            if ($next && $this->isSafeRedirectUrl($next)) {
                if ($returnAnchor) {
                    return $this->redirect($next . '#' . $returnAnchor);
                }
                return $this->redirect($next);
            }

            return $this->redirect($this->generateUrl('security_profil_role', [
                'role' => $role,
                'next' => $next,
                'return_anchor' => $returnAnchor,
            ]));
        }

        return $this->render($template, [
            'form' => $form->createView(),
            'next' => $next
        ]);
    }

    #[Route(path: '/mes-candidatures', name: 'my_applications_list')]
    public function myApplicationsAction(Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $filterForm = $this->handleApplicationsFilterForm($request, [
            'sort_field' => 'created',
            'sort_order' => 'desc',
            'status_filter' => null
        ]);

        $filters = $filterForm->getData();
        assert(is_array($filters));

        $params = [
            'applicant' => $user,
            'orderBy'   => $filters['sort_field'],
            'status'    => $filters['status_filter'],
            'sort'      => $filters['sort_order']
        ];

        $applications = $this->em->getRepository(Application::class)->formFilter($params);

        $pagination = $paginator->paginate(
            $applications,
            $request->query->getInt('page', 1)
        );

        return $this->render('Security/myApplications.html.twig', [
            "applications" => $pagination,
            'filterForm' => $filterForm->createView()
        ]);
    }

    #[Route(path: '/mes-candidatures/{id}', name: 'my_application_show')]
    public function showMyApplicationAction(Application $application): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        assert($user instanceof User);

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($application->getProjectHolder() !== $user && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette candidature.');
        }

        $repository = $this->em->getRepository(Application::class);

        $prevApplication = $repository->getApplicantPrevApplication($application, $user);
        $nextApplication = $repository->getApplicantNextApplication($application, $user);

        return $this->render('Security/showMyApplication.html.twig', [
            'prevApplication'   => $prevApplication,
            'nextApplication'   => $nextApplication,
            'application'       => $application
        ]);
    }

    #[Route(path: '/document/{id}/delete', name: 'profile_removedocument', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeDocumentAction(int $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('remove_user_document_' . $id, $request->request->getString('token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $userDocument = $this->em->getRepository(UserDocument::class)->find($id);
        if (!$userDocument) {
            throw $this->createNotFoundException('Document non trouvé.');
        }

        $currentUser = $this->getUser();
        $owner = $userDocument->getProjectHolder() ?: $userDocument->getUser();
        if (!$currentUser instanceof User || !$owner || $owner->getId() !== $currentUser->getId()) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce document.');
        }

        $this->em->remove($userDocument);
        $this->em->flush();

        $this->addFlash('success', 'Le document a été supprimé.');

        $serviceUrl = $request->get('service');
        if (is_string($serviceUrl) && str_starts_with($serviceUrl, '/') && !str_starts_with($serviceUrl, '//')) {
            return $this->redirect($serviceUrl);
        }

        $referer = $request->headers->get('Referer');
        if ($referer && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
            $refererPath = parse_url($referer, PHP_URL_PATH);
            if ($refererPath && str_contains($refererPath, '/apply')) {
                return $this->redirect($referer);
            }
        }

        $url = $this->generateUrl('security_profil');
        if ($request->get('anchor')) {
            $url .= '#' . $request->query->getString('anchor');
        } else {
            $url .= '#four';
        }

        return $this->redirect($url);
    }

    private function isSafeRedirectUrl(string $url): bool
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }
        if (!empty($parsed['host']) || !empty($parsed['scheme'])) {
            return false;
        }
        return isset($parsed['path']) && str_starts_with($parsed['path'], '/');
    }

    /** @param array<string, mixed> $data */
    protected function handleApplicationsFilterForm(Request $request, array $data): FormInterface
    {
        $builder = $this->formFactory->createNamedBuilder('filter',
            \Symfony\Component\Form\Extension\Core\Type\FormType::class,
            $data, [
                'action' => $this->generateUrl('my_applications_list'),
                'method' => 'get',
                'csrf_protection' => false
            ]);

        $builder->add('sort_field', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'required' => false,
            'choices' => array_flip([
                'type' => 'Type de local',
                'limitAvailability' => 'Date de clôture',
                'city' => 'Localité',
                'name' => 'Nom du bâtiment'
            ]),
            'placeholder' => 'Trier par',
            'empty_data' => ''
        ]);

        $builder->add('status_filter', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'required' => false,
            'choices' => array_flip([
                'draft' => 'À compléter',
                'sent'  => 'Envoyées',
                'accepted' => 'Acceptées',
                'rejected'  => 'Refusées',
            ]),
            'placeholder' => 'Filtrer par',
            'empty_data' => ''
        ]);

        $builder->add('sort_order', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'required' => false,
            'expanded' => true,
            'placeholder' => false,
            'choices' => array_flip([
                'asc' => 'Trier par ordre croissant',
                'desc' => 'Trier par ordre décroissant'
            ]),
            'empty_data' => 'desc'
        ]);

        $form = $builder->getForm();
        $form->handleRequest($request);

        return $form;
    }
}
