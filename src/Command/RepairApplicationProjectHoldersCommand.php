<?php

namespace App\Command;

use App\Entity\Application;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:repair-application-project-holders',
    description: 'Rattache les candidatures orphelines à un porteur de projet (rattrapage migration).',
)]
class RepairApplicationProjectHoldersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule sans écrire en base')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre max de candidatures à traiter', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $limit = max(0, (int) $input->getOption('limit'));

        $qb = $this->em->createQueryBuilder()
            ->select('a', 's')
            ->from(Application::class, 'a')
            ->join('a.space', 's')
            ->where('a.projectHolder IS NULL')
            ->andWhere('a.status != :draft')
            ->setParameter('draft', Application::DRAFT_STATUS)
            ->orderBy('a.id', 'ASC');

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        /** @var Application[] $applications */
        $applications = $qb->getQuery()->getResult();
        $updated = 0;
        $skipped = 0;

        foreach ($applications as $application) {
            $holder = $this->findCandidateHolder($application);
            if (!$holder instanceof User) {
                ++$skipped;
                continue;
            }

            ++$updated;
            if (!$dryRun) {
                $application->setProjectHolder($holder);
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $io->title('Rattrapage project_holder_id');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Candidatures sans porteur analysées', (string) count($applications)],
                ['Rattachements proposés / effectués', (string) $updated],
                ['Non résolues (ambiguïté ou aucun candidat)', (string) $skipped],
                ['Mode', $dryRun ? 'dry-run' : 'écriture'],
            ]
        );

        if ($skipped > 0) {
            $io->warning(
                'Certaines candidatures n\'ont pas pu être rattachées automatiquement. '
                .'Assignez le porteur manuellement dans Sonata ou fournissez une source SQL de prod.'
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Heuristique : un seul porteur plausible sur l\'espace (profil proche + non propriétaire).
     */
    private function findCandidateHolder(Application $application): ?User
    {
        $conn = $this->em->getConnection();
        $spaceId = $application->getSpace()?->getId();
        if (null === $spaceId) {
            return null;
        }

        $sql = <<<'SQL'
SELECT u.id
FROM fos_user u
WHERE u.id NOT IN (SELECT DISTINCT owner_id FROM space WHERE owner_id IS NOT NULL)
  AND (u.roles IS NULL OR (
        u.roles NOT LIKE '%"ROLE_OWNER"%'
    AND u.roles NOT LIKE '%"ROLE_ADMIN"%'
    AND u.roles NOT LIKE '%"ROLE_SUPER_ADMIN"%'
  ))
  AND (
        u.wishedSize <=> :wishedSize
    OR u.company_status <=> :companyStatus
  )
  AND EXISTS (
        SELECT 1 FROM user_document d WHERE d.projectHolder_id = u.id
  )
SQL;

        $ids = $conn->fetchFirstColumn($sql, [
            'wishedSize' => $application->getWishedSize(),
            'companyStatus' => $application->getCompanyStatus(),
        ]);

        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (count($ids) !== 1) {
            return null;
        }

        $user = $this->em->getRepository(User::class)->find($ids[0]);

        return $user instanceof User ? $user : null;
    }
}
