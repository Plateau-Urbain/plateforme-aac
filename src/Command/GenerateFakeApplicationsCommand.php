<?php

namespace App\Command;

use App\Entity\Application;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-fake-applications',
    description: 'Génère des candidatures fake pour un appel à candidatures',
)]
class GenerateFakeApplicationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('space-id', InputArgument::REQUIRED, 'ID de l\'espace (AAC)')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Nombre de candidatures à créer', 10)
            ->addOption('status', 's', InputOption::VALUE_OPTIONAL, 'Statut (draft, unread, awaiting, accepted, rejected) ou "random"', 'random')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $spaceId      = $input->getArgument('space-id');
        $count        = (int) $input->getOption('count');
        $statusOption = $input->getOption('status');

        $space = $this->em->getRepository(\App\Entity\Space::class)->find($spaceId);
        if (!$space) {
            $output->writeln("<error>Espace avec l'ID $spaceId introuvable.</error>");

            return Command::FAILURE;
        }

        $output->writeln("<info>Génération de $count candidatures fake pour l'espace: {$space->getName()}</info>");

        $categories = $this->em->getRepository(\App\Entity\Category::class)->findAll();
        if (empty($categories)) {
            $output->writeln('<error>Aucune catégorie trouvée. Veuillez créer des catégories d\'abord.</error>');

            return Command::FAILURE;
        }

        $statuses = [
            Application::DRAFT_STATUS,
            Application::UNREAD_STATUS,
            Application::WAIT_STATUS,
            Application::ACCEPT_STATUS,
            Application::REJECT_STATUS,
        ];

        $useRandomStatus = $statusOption === 'random';
        if (!$useRandomStatus && !in_array($statusOption, $statuses, true)) {
            $output->writeln("<error>Statut invalide: $statusOption</error>");

            return Command::FAILURE;
        }

        $firstNames   = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Julie', 'Thomas', 'Camille', 'Antoine', 'Emma'];
        $lastNames    = ['Dupont', 'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy'];
        $companies    = ['Tech Solutions', 'Innovation Lab', 'Creative Studio', 'Digital Agency', 'Startup Hub'];
        $projectNames = ['Projet Innovant', 'Startup Tech', 'Studio Créatif', 'Agence Digital', 'Espace Co-working'];

        $created = 0;
        $skipped = 0;

        for ($i = 1; $i <= $count; $i++) {
            $email = "fake.candidature.{$spaceId}.{$i}@test.local";

            if ($this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $output->writeln("<comment>Utilisateur $email existe déjà, passage au suivant…</comment>");
                ++$skipped;
                continue;
            }

            $user = new User();
            $user->setEmail($email);
            $user->setEnabled(true);
            $user->setTypeUser(User::PORTEUR);
            $user->setFirstName($firstNames[array_rand($firstNames)]);
            $user->setLastName($lastNames[array_rand($lastNames)]);
            $user->setCivility(User::MISTER);
            $user->setCompany($companies[array_rand($companies)] . ' ' . $i);
            $user->setCompanyStatus('SARL');
            $user->setAddress('123 Rue de Test');
            $user->setZipcode('75001');
            $user->setCity('Paris');
            $user->setCompanyPhone('01' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT));
            $user->setCompanyDescription("Description de l'entreprise fake numéro $i");
            $user->setProjectDescription("Projet de test numéro $i pour l'espace {$space->getName()}");
            $user->setUsageDuration(random_int(6, 36));
            $user->setLengthTypeOccupation(Application::MONTH_TYPE);
            $user->setWishedSize(random_int(20, 200));
            $user->setUsageDate(new \DateTime('+' . random_int(1, 12) . ' months'));

            // Mot de passe aléatoire (jamais utilisé)
            $plainPassword = bin2hex(random_bytes(16));
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

            $this->em->persist($user);

            $application = new Application();
            $application->setSpace($space);
            $application->setProjectHolder($user);
            $application->setName($projectNames[array_rand($projectNames)] . ' ' . $i);
            $application->setDescription("Candidature fake numéro $i générée automatiquement.");
            $application->setCategory($categories[array_rand($categories)]);
            $application->setWishedSize(random_int(20, 200));
            $application->setLengthOccupation(random_int(6, 36));
            $application->setLengthTypeOccupation(Application::MONTH_TYPE);
            $application->setStartOccupation(new \DateTime('+' . random_int(1, 12) . ' months'));
            $application->setOpenToGlobalProject((bool) random_int(0, 1));
            if ($application->getOpenToGlobalProject()) {
                $application->setContribution("Contribution au projet global pour la candidature $i");
            }
            $application->setDevenirSocietaire((bool) random_int(0, 1));
            $application->setSelected(false);
            $application->setStatus($useRandomStatus ? $statuses[array_rand($statuses)] : $statusOption);

            $this->em->persist($application);
            $this->em->flush();

            ++$created;
            $output->writeln("<info>✓ Candidature $i/$count créée : $email (statut : {$application->getStatus()})</info>");
        }

        $output->writeln('');
        $output->writeln("<info>Terminé ! $created candidatures créées, $skipped ignorées.</info>");

        return Command::SUCCESS;
    }
}
