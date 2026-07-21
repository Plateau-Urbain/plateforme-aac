<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\ApplicationFile;
use App\Entity\Space;
use App\Entity\User;
use App\Form\ApplicationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Space controller.
 */
#[Route('/espaces')]
class SpaceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface     $em,
        private readonly UserRepository             $userRepository,
        private readonly UserCheckerInterface       $userChecker,
        private readonly TokenStorageInterface      $tokenStorage,
        private readonly EventDispatcherInterface   $eventDispatcher,
        private readonly LoggerInterface            $logger,
    ) {}

    /**
     * Fiche d'un espace
     */
    #[Route('/fiche/{id}', name: 'space_show', methods: ['GET', 'POST'])]
    public function showAction(Space $space, Request $request): Response
    {
        $user     = $this->getUser();
        $isAdmin  = $user && $this->isGranted('ROLE_ADMIN');
        $canView  = $user && ($space->isOwner($user) || $isAdmin);

        if ((!$space->isEnabled() || $space->isClosed()) && !$canView) {
            return $this->redirect($this->generateUrl('search_index'));
        }

        if ($user === null) {
            return $this->render('Space/show.html.twig', ['space' => $space]);
        }

        $application = $this->em->getRepository(Application::class)->findOneBy([
            'projectHolder' => $user,
            'space'         => $space,
        ]);

        return $this->render('Space/show.html.twig', [
            'space'       => $space,
            'application' => $application,
        ]);
    }

    /**
     * Formulaire de candidature d'un espace.
     * @return array<string, mixed>|Response
     */
    #[Route('/fiche/{space}/apply', name: 'space_apply')]
    public function applyAction(Space $space, Request $request, MailerInterface $mailer): array|Response
    {
        $connect_after_application = false;

        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $this->getUser();
            assert($user instanceof User);

            if (!$user->isProfileComplete()) {
                $missing = $user->getMissingProfileFields();
                $isNewUser = empty($user->getFirstname());
                if (!empty($missing)) {
                    if ($isNewUser) {
                        $this->addFlash('warning', sprintf(
                            'Bienvenue ! Veuillez compléter votre profil avant de pouvoir candidater. Champs manquants : %s.',
                            implode(', ', $missing)
                        ));
                    } else {
                        $this->addFlash('warning', sprintf(
                            'Veuillez mettre à jour votre profil avant de pouvoir candidater. Champs manquants : %s.',
                            implode(', ', $missing)
                        ));
                    }
                } else {
                    if ($isNewUser) {
                        $this->addFlash('warning', 'Bienvenue ! Veuillez compléter votre profil avant de pouvoir candidater.');
                    } else {
                        $this->addFlash('warning', 'Veuillez mettre à jour votre profil avant de pouvoir candidater.');
                    }
                }

                $next      = $this->generateUrl('space_apply', ['space' => $space->getId()]);
                $profilUrl = $this->generateUrl('security_profil', ['next' => $next]) . '#two';

                return $this->redirect($profilUrl);
            }
        } else {
            // Utilisateur anonyme : créer un compte temporaire rempli par le formulaire
            $user                      = new User();
            $user->setEnabled(true);
            $connect_after_application = true;
        }

        if ($space->isClosed()) {
            return $this->redirect($this->generateUrl('search_index'));
        }

        if ($user->getId() && ($space->isOwner($user) || $user->isProprio())) {
            return $this->redirect($this->generateUrl('search_index'));
        }

        $application = $this->em->getRepository(Application::class)->findOneBy([
            'projectHolder' => $user,
            'space'         => $space,
        ]);

        if (!$application instanceof Application) {
            $application = Application::createFromUser($user);
            $application->setSpace($space);

            // Persister l'utilisateur anonyme dès l'ouverture du formulaire
            if ($user->getId() === null) {
                $this->persistUser($user);
            }
        } elseif ($application->getStatus() === Application::UNREAD_STATUS) {
            return $this->redirectToRoute('my_application_show', ['id' => $application->getId()]);
        }

        if ($application->getId() !== null) {
            $this->updateApplicationFromUserProfile($application, $user);
        }

        $form = $this->createForm(ApplicationType::class, $application, [
            'action'                 => $this->generateUrl('space_apply', ['space' => $space->getId()]),
            'user'                   => $user,
            'freeze_profile_sections' => true,
        ]);

        if (!$form->has('save')) {
            $form->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
                'label' => 'Enregistrer en brouillon',
                'attr'  => ['class' => 'btn btn-default-color submit_form'],
            ]);
        }

        if ($request->isMethod('POST')) {
            $uploadErrors = $this->collectUploadErrors($request);
            foreach ($uploadErrors as $errorMsg) {
                $form->addError(new \Symfony\Component\Form\FormError($errorMsg));
            }
        }

        $form->handleRequest($request);

        // Vérifier la duplication d'email pour un utilisateur anonyme
        if ($form->isSubmitted() && $user->getId() === null) {
            $userInfoData = $form->get('projectHolder')->get('userInfo')->getData();
            $email = $userInfoData instanceof \App\Entity\User ? $userInfoData->getEmail() : '';
            if ($this->userRepository->findOneBy(['email' => $email])) {
                $this->addFlash('error_sign', 'Cette adresse email est déjà utilisée.');

                return $this->redirectToRoute('homepage');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $application->setProjectHolder($user);

            $formData    = $request->request->all($form->getName());
            $intent      = $formData['intent'] ?? '';
            $saveBtn = $form->get('save');
            $isSaveIntent = ($saveBtn instanceof ClickableInterface && $saveBtn->isClicked()) || $intent === 'save';
            $submitBtn = $form->get('submit');
            $isSubmitIntent = ($submitBtn instanceof ClickableInterface && $submitBtn->isClicked()) || $intent === 'submit';

            // Vérifier l'état de l'espace pour soumission définitive
            if ($isSubmitIntent && (!$space->isEnabled() || $space->isClosed())) {
                if ($space->isClosed()) {
                    $this->addFlash('error', 'Cet espace a été fermé définitivement. Votre candidature ne peut pas être soumise.');

                    return $this->redirectToRoute('space_show', ['id' => $space->getId()]);
                }

                $this->addFlash('warning', "Cet espace a été temporairement suspendu. Votre candidature a été sauvegardée en brouillon.");
                $application->setStatus(Application::DRAFT_STATUS);
                $this->persistUser($user);
                $this->em->persist($application);
                $this->em->persist($user);
                $this->em->flush();

                return $this->redirectToRoute('my_applications_list');
            }

            if ($isSubmitIntent) {
                $application->setStatus(Application::UNREAD_STATUS);
            } elseif ($isSaveIntent) {
                $application->setStatus(Application::DRAFT_STATUS);
            }

            $this->persistUser($user);
            $this->em->persist($application);
            $this->em->persist($user);
            $this->em->flush();

            try {
                $confirmEmail = (new Email())
                    ->subject('Confirmation de candidature')
                    ->from($this->getStringParam('mail_confirmation_from'))
                    ->to($application->getProjectHolder()?->getEmail() ?? '')
                    ->html($this->renderView('Email/candidacy_confirmation.html.twig', ['space' => $space]));

                $mailer->send($confirmEmail);
            } catch (\Exception $e) {
                $this->logger->error('Échec envoi email confirmation candidature', [
                    'exception'      => $e,
                    'application_id' => $application->getId(),
                ]);
            }

            if ($connect_after_application) {
                $this->autoLoginUser($user, $request);
            }

            if ($application->getStatus() === Application::DRAFT_STATUS) {
                return $this->redirectToRoute('my_applications_list');
            }

            return $this->redirectToRoute('my_application_show', ['id' => $application->getId()]);
        }

        return $this->render('Space/apply.html.twig', [
            'application' => $application,
            'space'       => $space,
            'form'        => $form->createView(),
        ]);
    }

    #[Route('/file/{id}/delete', name: 'space_removefile', requirements: ['id' => '\d+'])]
    public function removeFileAction(int $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('remove_file_' . $id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $applicationFile = $this->em->getRepository(ApplicationFile::class)->find($id);
        if (!$applicationFile) {
            throw $this->createNotFoundException('Fichier non trouvé.');
        }

        $application = $applicationFile->getApplication();
        $currentUser = $this->getUser();
        if (!$currentUser || $application->getProjectHolder() !== $currentUser) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce fichier.');
        }

        $this->em->remove($applicationFile);
        $this->em->flush();

        $this->addFlash('success', 'Le document a été supprimé.');

        $serviceUrl = $request->get('service');
        if (is_string($serviceUrl) && str_starts_with($serviceUrl, '/') && !str_starts_with($serviceUrl, '//')) {
            return $this->redirect($serviceUrl);
        }

        return $this->redirect($this->generateUrl('space_show', [
            'id' => $applicationFile->getApplication()->getSpace()?->getId(),
        ]));
    }

    #[Route('/confirmation', name: 'space_confirmation')]
    public function confirmationAction(): Response
    {
        return $this->render('Space/confirmation.html.twig');
    }

    #[Route('/check-status/{id}', name: 'space_check_status')]
    public function checkStatusAction(Space $space): JsonResponse
    {
        return new JsonResponse([
            'available' => $space->isEnabled() && !$space->isClosed(),
            'closed'    => $space->isClosed(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Méthodes privées
    // -------------------------------------------------------------------------

    /**
     * Persiste un utilisateur et hache son mot de passe si un plainPassword est présent.
     */
    private function persistUser(User $user): void
    {
        $this->em->persist($user);
    }

    /**
     * Connecte automatiquement un utilisateur après une candidature anonyme.
     */
    private function autoLoginUser(User $user, Request $request): void
    {
        try {
            $this->userChecker->checkPreAuth($user);
            $this->userChecker->checkPostAuth($user);
        } catch (AuthenticationException $e) {
            $this->logger->warning('Auto-login bloqué après candidature anonyme', [
                'user_id' => $user->getId(),
                'reason'  => $e->getMessage(),
            ]);

            return;
        }

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
        $request->getSession()->migrate(false);

        $event = new InteractiveLoginEvent($request, $token);
        $this->eventDispatcher->dispatch($event, 'security.interactive_login');
    }

    /**
     * Collecte les erreurs d'upload PHP et retourne les messages lisibles.
     * @return list<string>
     */
    private function collectUploadErrors(Request $request): array
    {
        $errors = [];
        foreach ($request->files->all() as $fileArray) {
            $files = is_array($fileArray) ? $fileArray : [$fileArray];
            foreach ($files as $file) {
                if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                    continue;
                }
                $errorCode = $file->getError();
                if ($errorCode === UPLOAD_ERR_OK) {
                    continue;
                }
                $msg = match ($errorCode) {
                    UPLOAD_ERR_INI_SIZE  => sprintf('"%s" dépasse la taille max serveur (%s).', $file->getClientOriginalName(), ini_get('upload_max_filesize')),
                    UPLOAD_ERR_FORM_SIZE => sprintf('"%s" dépasse la taille max (10 Mo).', $file->getClientOriginalName()),
                    UPLOAD_ERR_PARTIAL   => sprintf('"%s" n\'a été que partiellement téléchargé.', $file->getClientOriginalName()),
                    UPLOAD_ERR_NO_FILE   => null,
                    UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Erreur serveur : impossible d\'écrire le fichier.',
                    default              => sprintf('Erreur inconnue lors du téléchargement de "%s".', $file->getClientOriginalName()),
                };
                if ($msg) {
                    $errors[] = $msg;
                }
            }
        }

        return $errors;
    }

    /**
     * Met à jour une candidature avec les données manquantes du profil utilisateur.
     */
    private function updateApplicationFromUserProfile(Application $application, User $user): void
    {
        if (!$application->getProjectHolder()) {
            $application->setProjectHolder($user);
        }

        if (empty($application->getDescription()) && !empty($user->getProjectDescription())) {
            $application->setDescription($user->getProjectDescription());
        }
        if (empty($application->getLengthOccupation()) && !empty($user->getUsageDuration())) {
            $application->setLengthOccupation((string) $user->getUsageDuration());
        }
        if (empty($application->getLengthTypeOccupation()) && !empty($user->getLengthTypeOccupation())) {
            $application->setLengthTypeOccupation($user->getLengthTypeOccupation());
        }
        if (empty($application->getWishedSize()) && !empty($user->getWishedSize())) {
            $application->setWishedSize($user->getWishedSize());
        }
    }

    private function getStringParam(string $name): string
    {
        $value = $this->getParameter($name);
        assert(is_string($value));
        return $value;
    }
}
