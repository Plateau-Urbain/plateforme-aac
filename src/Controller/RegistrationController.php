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
        $user->setEnabled(true);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $email = strtolower(trim((string) $user->getEmail()));
            if ($email !== '' && $this->userRepository->findOneBy(['email' => $email]) instanceof User) {
                $this->addFlash('error_sign', 'Cette adresse email est déjà utilisée.');

                return $this->redirectToRoute('homepage');
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

            $this->em->persist($user);
            $this->em->flush();

            $this->sendConfirmationEmail($user);

            return $this->redirectToRoute('homepage', ['confirm_inscription' => 1]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash(
                'error_sign',
                'Erreur lors de l\'inscription, veuillez vérifier votre e-mail et votre mot de passe.'
            );

            return $this->redirectToRoute('homepage');
        }

        return $this->render('bundles/FOSUserBundle/views/Registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function sendConfirmationEmail(User $user): void
    {
        try {
            $scheme = $this->baseUrl !== 'localhost' ? 'https' : 'http';
            $context = $this->router->getContext();
            $context->setHost($this->baseUrl);
            $context->setScheme($scheme);

            $loginUrl = $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $homeUrl = $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new Email())
                ->subject('Votre inscription à la plateforme Plateau-Urbain')
                ->from($this->mailConfirmationFrom)
                ->to((string) $user->getEmail())
                ->html($this->renderView('Email/confirm.html.twig', [
                    'url' => $loginUrl,
                    'email' => $user->getEmail(),
                    'homeurl' => $homeUrl,
                ]))
                ->text($this->renderView('Email/confirm.txt.twig', [
                    'url' => $loginUrl,
                    'email' => $user->getEmail(),
                    'homeurl' => $homeUrl,
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
