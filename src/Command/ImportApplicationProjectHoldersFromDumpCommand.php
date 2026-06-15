<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-application-project-holders-from-dump',
    description: 'Restaure application.project_holder_id depuis le dump prod (colonne projectHolder_id).',
)]
class ImportApplicationProjectHoldersFromDumpCommand extends Command
{
    private const HOLDER_COLUMN_INDEX = 7;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dump', null, InputOption::VALUE_REQUIRED, 'Chemin du fichier SQL', 'plateforme_prod-28052026.sql')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule sans écrire en base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $dumpPath = (string) $input->getOption('dump');

        if (!str_starts_with($dumpPath, '/')) {
            $dumpPath = \dirname(__DIR__, 2).'/'.$dumpPath;
        }

        if (!is_readable($dumpPath)) {
            $io->error(sprintf('Fichier dump introuvable : %s', $dumpPath));

            return Command::FAILURE;
        }

        $mappings = $this->extractHolderMappings($dumpPath);
        if ($mappings === []) {
            $io->error('Aucune candidature trouvée dans le dump (INSERT INTO `Application`).');

            return Command::FAILURE;
        }

        $conn = $this->em->getConnection();
        $updated = 0;
        $skipped = 0;

        foreach ($mappings as $applicationId => $holderId) {
            if ($holderId <= 0) {
                ++$skipped;
                continue;
            }

            $userExists = (int) $conn->fetchOne(
                'SELECT COUNT(*) FROM fos_user WHERE id = :id',
                ['id' => $holderId]
            );

            if ($userExists === 0) {
                ++$skipped;
                continue;
            }

            if (!$dryRun) {
                $conn->executeStatement(
                    'UPDATE Application SET projectHolder_id = :holder WHERE id = :id',
                    ['holder' => $holderId, 'id' => $applicationId]
                );
            }

            ++$updated;
        }

        $withHolder = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM Application WHERE projectHolder_id IS NOT NULL'
        );

        $io->title('Import project_holder_id depuis dump prod');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Lignes lues dans le dump', (string) count($mappings)],
                ['Mises à jour effectuées', (string) $updated],
                ['Ignorées (holder vide ou user absent)', (string) $skipped],
                ['Total avec project_holder_id en base', (string) $withHolder],
                ['Mode', $dryRun ? 'dry-run' : 'écriture'],
            ]
        );

        if (!$dryRun) {
            $io->success('Import terminé. Rechargez les listes Sonata Candidatures / Espaces.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, int> applicationId => projectHolderId
     */
    private function extractHolderMappings(string $dumpPath): array
    {
        $mappings = [];
        $handle = fopen($dumpPath, 'rb');
        if ($handle === false) {
            return [];
        }

        while (($line = fgets($handle)) !== false) {
            if (!str_contains($line, 'INSERT INTO `Application` VALUES')) {
                continue;
            }

            $offset = strpos($line, 'VALUES');
            if ($offset === false) {
                continue;
            }

            $pos = $offset;
            $lineLength = strlen($line);

            while ($pos < $lineLength) {
                $open = strpos($line, '(', $pos);
                if ($open === false) {
                    break;
                }

                $tuple = $this->readParenthesizedSegment($line, $open);
                if ($tuple === null) {
                    break;
                }

                $fields = $this->parseSqlTuple($tuple);
                if (isset($fields[0], $fields[self::HOLDER_COLUMN_INDEX])) {
                    $applicationId = (int) $this->unquoteSqlValue($fields[0]);
                    $holderId = (int) $this->unquoteSqlValue($fields[self::HOLDER_COLUMN_INDEX]);
                    if ($applicationId > 0) {
                        $mappings[$applicationId] = $holderId;
                    }
                }

                $pos = $open + strlen($tuple) + 2;
            }
        }

        fclose($handle);

        return $mappings;
    }

    private function readParenthesizedSegment(string $line, int $openPos): ?string
    {
        if (($line[$openPos] ?? '') !== '(') {
            return null;
        }

        $depth = 0;
        $inString = false;
        $length = strlen($line);

        for ($i = $openPos; $i < $length; ++$i) {
            $char = $line[$i];

            if ($char === "'" && ($i === 0 || $line[$i - 1] !== '\\')) {
                $inString = !$inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '(') {
                ++$depth;
                continue;
            }

            if ($char === ')') {
                --$depth;
                if ($depth === 0) {
                    return substr($line, $openPos + 1, $i - $openPos - 1);
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function parseSqlTuple(string $tuple): array
    {
        $fields = [];
        $current = '';
        $inString = false;
        $len = strlen($tuple);

        for ($i = 0; $i < $len; ++$i) {
            $char = $tuple[$i];

            if ($char === "'" && ($i === 0 || $tuple[$i - 1] !== '\\')) {
                $inString = !$inString;
                $current .= $char;
                continue;
            }

            if ($char === ',' && !$inString) {
                $fields[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $fields[] = trim($current);
        }

        return $fields;
    }

    private function unquoteSqlValue(string $value): string
    {
        if (strtoupper($value) === 'NULL') {
            return '0';
        }

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return str_replace(["\\'", '\\r', '\\n', '\\\\'], ["'", "\r", "\n", '\\'], substr($value, 1, -1));
        }

        return $value;
    }
}
