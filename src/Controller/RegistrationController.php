<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
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

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $mailConfirmationFrom,
        private readonly string $baseUrl,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route(path: '/register/', name: 'fos_user_registration_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $user = new User();
        $user->setTypeUser(User::PORTEUR);
        $user->setEnabled(false);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $email = strtolower(trim((string) $user->getEmail()));
            if ($email !== '' && $this->userRepository->findOneBy(['email' => $email]) instanceof User) {
                $resetUrl = $this->generateUrl('fos_user_resetting_request');
                $this->addFlash(
                    'error_sign',
                    'Cette adresse e-mail est déjà utilisée. Veuillez vous connecter ou <a href="' . $resetUrl . '" style="text-decoration: underline; font-weight: bold; color: inherit;">réinitialiser votre mot de passe</a> si vous l\'avez oublié.'
                );

                return $this->redirectToRoute('app_login');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $email = strtolower(trim((string) $user->getEmail()));
            $user->setEmail($email);
            $user->setEmailCanonical($email);

            $plainPassword = $user->getPlainPassword();
            if ($plainPassword !== null && $plainPassword !== '') {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            }
            $user->setPlainPassword(null);

            if ($user->getCreatedAt() === null) {
                $user->setCreatedAt(new \DateTime());
            }

            $user->setEnabled(false);
            $user->setConfirmationToken(bin2hex(random_bytes(32)));

            $this->em->persist($user);
            $this->em->flush();

            $this->sendConfirmationEmail($user);

            return $this->redirectToRoute('fos_user_registration_check_email', [
                'email' => $user->getEmail(),
            ]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $captchaErrors = $form->get('captcha')->getErrors();
            if (count($captchaErrors) > 0) {
                $this->addFlash(
                    'error_sign',
                    'Le code de sécurité (captcha) est incorrect. Veuillez réessayer.'
                );

                return $this->redirectToRoute('fos_user_registration_register');
            } else {
                $this->addFlash(
                    'error_sign',
                    'Erreur lors de l\'inscription, veuillez vérifier votre e-mail et votre mot de passe.'
                );

                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('bundles/FOSUserBundle/views/Registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/register/check-email', name: 'fos_user_registration_check_email', methods: ['GET'])]
    public function checkEmail(Request $request): Response
    {
        $email = (string) $request->query->get('email', '');

        return $this->render('bundles/FOSUserBundle/views/Registration/check_email.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route(path: '/register/confirm/{token}', name: 'fos_user_registration_confirm', methods: ['GET'])]
    public function confirm(string $token): Response
    {
        $user = $this->userRepository->findOneBy(['confirmationToken' => $token]);
        if (!$user instanceof User) {
            $this->addFlash('error_sign', 'Ce lien d\'activation est invalide ou a déjà été utilisé.');

            return $this->redirectToRoute('app_login');
        }

        $user->setEnabled(true);
        $user->setConfirmationToken(null);
        $this->em->flush();

        $this->addFlash('success_msg', 'Votre compte est activé. Vous pouvez vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    private function sendConfirmationEmail(User $user): void
    {
        try {
            $scheme = $this->baseUrl !== 'localhost' ? 'https' : 'http';
            $context = $this->router->getContext();
            $context->setHost($this->baseUrl);
            $context->setScheme($scheme);

            $confirmUrl = $this->generateUrl(
                'fos_user_registration_confirm',
                ['token' => (string) $user->getConfirmationToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $homeUrl = $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new Email())
                ->subject('Votre inscription à la plateforme Plateau-Urbain')
                ->from($this->mailConfirmationFrom)
                ->to((string) $user->getEmail())
                ->html($this->renderView('Email/confirm.html.twig', [
                    'url' => $confirmUrl,
                    'email' => $user->getEmail(),
                    'homeurl' => $homeUrl,
                    'rooturl' => $homeUrl,
                ]))
                ->text($this->renderView('Email/confirm.txt.twig', [
                    'url' => $confirmUrl,
                    'email' => $user->getEmail(),
                    'homeurl' => $homeUrl,
                    'rooturl' => $homeUrl,
                ]));

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Échec envoi email inscription', [
                'user' => $user->getEmail(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
