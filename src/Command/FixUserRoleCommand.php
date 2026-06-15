<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fix-user-role',
    description: 'Corrige les rôles des utilisateurs propriétaires',
)]
class FixUserRoleCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email de l\'utilisateur spécifique')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Corriger tous les utilisateurs propriétaires');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email  = $input->getArgument('email');
        $fixAll = $input->getOption('all');

        if ($email) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user) {
                $output->writeln("<error>Utilisateur non trouvé avec l'email : $email</error>");

                return Command::FAILURE;
            }
            $this->fixUserRole($user, $output);
        } elseif ($fixAll) {
            $users = $this->em->getRepository(User::class)->findBy(['typeUser' => User::PROPRIO]);
            $output->writeln('<info>Correction de ' . count($users) . ' utilisateurs propriétaires…</info>');
            foreach ($users as $user) {
                $this->fixUserRole($user, $output);
            }
        } else {
            $output->writeln('<error>Veuillez spécifier un email ou utiliser --all</error>');

            return Command::FAILURE;
        }

        $this->em->flush();
        $output->writeln('<info>Correction terminée !</info>');

        return Command::SUCCESS;
    }

    private function fixUserRole(User $user, OutputInterface $output): void
    {
        $roles = $user->getRoles();

        if (!in_array('ROLE_OWNER', $roles, true)) {
            $user->addRole('ROLE_OWNER');
            $output->writeln('<info>✓ Ajouté ROLE_OWNER à ' . $user->getEmail() . '</info>');
        } else {
            $output->writeln('<comment>- ' . $user->getEmail() . ' a déjà ROLE_OWNER</comment>');
        }
    }
}
