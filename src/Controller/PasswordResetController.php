<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
class PasswordResetController extends AbstractController
{
    private const TOKEN_TTL_SECONDS = 86400;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly RouterInterface $router,
        private readonly string $mailConfirmationFrom,
        private readonly string $baseUrl,
    ) {
    }

    #[Route(path: '/mot-de-passe-oublie', name: 'password_reset_request_fr', methods: ['GET'])]
    public function requestFrenchAlias(): Response
    {
        return $this->redirectToRoute('fos_user_resetting_request');
    }

    #[Route(path: '/resetting/request', name: 'fos_user_resetting_request', methods: ['GET'])]
    public function request(): Response
    {
        return $this->render('bundles/FOSUserBundle/views/Resetting/request.html.twig');
    }

    #[Route(path: '/resetting/send-email', name: 'fos_user_resetting_send_email', methods: ['POST'])]
    public function sendEmail(Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('resetting_request', $token)) {
            $this->addFlash('error_sign', 'Jeton de sécurité invalide. Veuillez réessayer.');

            return $this->redirectToRoute('fos_user_resetting_request');
        }

        $identifier = trim((string) $request->request->get('username', ''));
        if ($identifier !== '') {
            $user = $this->userRepository->findByEmailOrUsername($identifier);
            if ($user instanceof User) {
                $user->setConfirmationToken(bin2hex(random_bytes(32)));
                $user->setPasswordRequestedAt(new \DateTime());
                $this->em->flush();
                $this->sendResettingEmail($user);
            }
        }

        return $this->redirectToRoute('fos_user_resetting_check_email');
    }

    #[Route(path: '/resetting/check-email', name: 'fos_user_resetting_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('bundles/FOSUserBundle/views/Resetting/check_email.html.twig', [
            'tokenLifetimeHours' => (int) (self::TOKEN_TTL_SECONDS / 3600),
        ]);
    }

    #[Route(path: '/resetting/reset/{token}', name: 'fos_user_resetting_reset', methods: ['GET', 'POST'])]
    public function reset(Request $request, string $token): Response
    {
        $user = $this->userRepository->findUserByValidResetToken($token, self::TOKEN_TTL_SECONDS);
        if (!$user instanceof User) {
            $this->addFlash('error_sign', 'Ce lien de réinitialisation est invalide ou a expiré.');

            return $this->redirectToRoute('fos_user_resetting_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            assert(is_string($plainPassword));
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $this->em->flush();

            $this->addFlash('success_msg', 'Votre mot de passe a été mis à jour. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('bundles/FOSUserBundle/views/Resetting/reset.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
        ]);
    }

    private function sendResettingEmail(User $user): void
    {
        try {
            $scheme = $this->baseUrl !== 'localhost' ? 'https' : 'http';
            $context = $this->router->getContext();
            $context->setHost($this->baseUrl);
            $context->setScheme($scheme);

            $resetUrl = $this->generateUrl(
                'fos_user_resetting_reset',
                ['token' => $user->getConfirmationToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new Email())
                ->subject('Réinitialisation de votre mot de passe Plateau Urbain')
                ->from($this->mailConfirmationFrom)
                ->to((string) $user->getEmail())
                ->html($this->renderView('Email/resetting.html.twig', [
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                    'tokenLifetimeHours' => (int) (self::TOKEN_TTL_SECONDS / 3600),
                ]))
                ->text($this->renderView('Email/resetting.txt.twig', [
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                    'tokenLifetimeHours' => (int) (self::TOKEN_TTL_SECONDS / 3600),
                ]));

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Échec envoi email réinitialisation mot de passe', [
                'user' => $user->getEmail(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
