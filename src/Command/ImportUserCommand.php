<?php

namespace App\Command;

use App\Entity\UseType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;

#[AsCommand(
    name: 'app:importusers',
    description: 'Import Users from CSV files',
)]
class ImportUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface             $mailer,
        private readonly TwigEnvironment             $twig,
        private readonly RouterInterface             $router,
        private readonly LoggerInterface             $logger,
        private readonly string                      $mailConfirmationFrom,
        private readonly string                      $baseUrl,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Importe les utilisateurs depuis un fichier CSV.')
            ->addArgument('csv', InputArgument::REQUIRED, 'Fichier CSV à importer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $useTypeRepo = $this->em->getRepository(UseType::class);
        $csvfile     = $input->getArgument('csv');

        $output->writeln('CSV Import : ' . $csvfile);

        $f = fopen($csvfile, 'r');
        if ($f === false) {
            $output->writeln('<error>Impossible d\'ouvrir ' . $csvfile . '</error>');

            return Command::FAILURE;
        }

        $n       = 0;
        $headers = fgetcsv($f);

        while (!feof($f)) {
            $a     = fgetcsv($f);
            $email = strtolower(trim($a[4] ?? ''));

            if ($email === '') {
                continue;
            }
            if (!str_contains($email, '@')) {
                $output->writeln("Adresse email invalide : $email");
                continue;
            }

            $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing !== null) {
                $output->writeln("Utilisateur déjà en base : $email");
                continue;
            }

            $user = new User();
            $user->setTypeUser(0); // porteur de projet
            $user->setCivility(str_replace('.', '', $a[1]));
            $user->setLastName($a[2]);
            $user->setFirstName($a[3]);
            $user->setEmail($email);
            $user->setBirthDay(self::convDate($a[5]));

            $phone = trim($a[6]);
            if (strlen($phone) === 9 && $phone[0] !== '0') {
                $phone = '0' . $phone;
            } elseif (str_starts_with($phone, '33')) {
                $phone = '+' . $phone;
            }
            $user->setPhone($phone);
            $user->setDescription($a[7]);

            $useTypeStr = trim($a[8]);
            $useType    = $useTypeRepo->findOneBy(['name' => $useTypeStr]);
            if ($useType !== null) {
                $user->setUseType($useType);
            } else {
                $output->writeln("Type d'usage introuvable : $useTypeStr");
            }

            $user->setProjectDescription($a[9]);

            $duree = $a[10];
            foreach (explode(' ', $duree) as $item) {
                if (is_numeric($item)) {
                    $user->setUsageDuration((int) $item);
                } else {
                    match (strtolower($item)) {
                        'an', 'ans' => $user->setLengthTypeOccupation(\App\Entity\Application::YEAR_TYPE),
                        'mois'      => $user->setLengthTypeOccupation('mois'),
                        default     => null,
                    };
                }
            }

            $surface = $a[11];
            foreach (explode(' ', $surface) as $item) {
                if (is_numeric($item)) {
                    $user->setWishedSize((int) $item);
                }
            }

            $user->setUsageDate(self::convDate($a[12]));
            $user->setCompany($a[13]);
            $user->setCompanyStatus($a[14]);
            $user->setCompanyCreationDate(self::convDate($a[15]));
            $user->setAddress($a[16]);
            $user->setZipcode($a[17]);
            $user->setCity($a[18]);
            $user->setCompanySite($a[19]);
            $user->setFacebookUrl($a[20]);
            $user->setTwitterUrl($a[21]);
            $user->setLinkedinUrl($a[22]);
            $user->setInstagramUrl($a[23]);
            $user->setGoogleUrl($a[25]);

            $newsletter = strtoupper(substr($a[26] ?? '', 0, 1));
            $user->setNewsletter($newsletter !== 'N');

            // Mot de passe temporaire aléatoire — l'utilisateur devra le réinitialiser
            $tempPassword = bin2hex(random_bytes(8));
            $user->setPassword($this->passwordHasher->hashPassword($user, $tempPassword));
            $user->setEnabled(true);

            $this->em->persist($user);
            $this->em->flush();

            $this->sendWelcomeEmail($user, $output);
            ++$n;
        }

        fclose($f);
        $output->writeln("$n utilisateur(s) importé(s).");

        return Command::SUCCESS;
    }

    /**
     * Envoie un email de bienvenue à l'utilisateur importé.
     * L'URL de login est incluse — une fonctionnalité de réinitialisation de mot de passe
     * doit être implémentée séparément (cf. TODO ci-dessous).
     *
     * TODO : remplacer l'URL de login par un lien de réinitialisation de mot de passe
     *        une fois le composant symfony/reset-password-bundle intégré.
     */
    private function sendWelcomeEmail(User $user, OutputInterface $output): void
    {
        try {
            $scheme = $this->baseUrl !== 'localhost' ? 'https' : 'http';
            $this->router->getContext()->setHost($this->baseUrl);
            $this->router->getContext()->setScheme($scheme);

            $loginUrl = $this->router->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $homeUrl  = $this->router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $output->writeln("Envoi email à : " . $user->getEmail());

            $html  = $this->twig->render('Email/confirm.html.twig', [
                'url'      => $loginUrl,
                'email'    => $user->getEmail(),
                'homeurl'  => $homeUrl,
            ]);
            $plain = $this->twig->render('Email/confirm.txt.twig', [
                'url'      => $loginUrl,
                'email'    => $user->getEmail(),
                'homeurl'  => $homeUrl,
            ]);

            $email = (new Email())
                ->subject('Votre inscription à la plateforme Plateau-Urbain')
                ->from($this->mailConfirmationFrom)
                ->to($user->getEmail())
                ->html($html)
                ->text($plain);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Échec envoi email import utilisateur', [
                'user'      => $user->getEmail(),
                'exception' => $e->getMessage(),
            ]);
            $output->writeln('<comment>Email non envoyé pour ' . $user->getEmail() . ' : ' . $e->getMessage() . '</comment>');
        }
    }

    private static function convDate(string $str): ?\DateTime
    {
        $d = explode('/', $str);
        if (count($d) < 3) {
            return null;
        }

        try {
            return new \DateTime(implode('/', array_reverse($d)));
        } catch (\Exception) {
            return null;
        }
    }
}
