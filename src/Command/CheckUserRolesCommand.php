<?php

namespace App\Command;

use App\Entity\Space;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:check-user-roles',
    description: 'Vérifie les rôles d\'un utilisateur',
)]
class CheckUserRolesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $output->writeln("<error>Utilisateur non trouvé avec l'email : $email</error>");

            return Command::FAILURE;
        }

        $output->writeln('<info>=== Informations utilisateur ===</info>');
        $output->writeln('ID: ' . $user->getId());
        $output->writeln('Email: ' . $user->getEmail());
        $output->writeln('TypeUser: ' . $user->getTypeUser());
        $output->writeln('Enabled: ' . ($user->isEnabled() ? 'Oui' : 'Non'));

        $output->writeln("\n<info>=== Rôles ===</info>");
        foreach ($user->getRoles() as $role) {
            $output->writeln('- ' . $role);
        }

        $output->writeln("\n<info>=== Méthodes de vérification ===</info>");
        $output->writeln('isProprio(): ' . ($user->isProprio() ? 'Oui' : 'Non'));
        $output->writeln('isPorteur(): ' . ($user->isPorteur() ? 'Oui' : 'Non'));

        $output->writeln("\n<info>=== Espaces de l'utilisateur ===</info>");
        $spaces = $this->em->getRepository(Space::class)->findBy(['owner' => $user]);
        $output->writeln("Nombre d'espaces : " . count($spaces));
        foreach ($spaces as $space) {
            $output->writeln('- ID: ' . $space->getId() . ', Nom: ' . $space->getName() . ', Publié: ' . ($space->isEnabled() ? 'Oui' : 'Non'));
        }

        return Command::SUCCESS;
    }
}
