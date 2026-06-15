<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Restaure useType_id, wishedSize, usageDuration, company_status, created_at sur fos_user
 * depuis un dump SQL prod (schéma dump 2026-05, useType_id en position 47).
 *
 * Alimente le graphique « Candidatures par type de projet » (projectHolder.useType).
 */
#[AsCommand(
    name: 'app:restore-user-profile-fields-from-dump',
    description: 'Restaure useType_id et champs profil porteur depuis plateforme_prod-*.sql',
)]
class RestoreUserProfileFieldsFromDumpCommand extends Command
{
    private const CREATED_AT_INDEX = 12;
    private const WISHED_SIZE_INDEX = 45;
    private const USAGE_DURATION_INDEX = 46;
    private const USE_TYPE_INDEX = 47;
    private const COMPANY_STATUS_INDEX = 50;

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

        $rows = $this->extractUserRows($dumpPath);
        if ($rows === []) {
            $io->warning('Aucune ligne fos_user trouvée dans le dump.');

            return Command::FAILURE;
        }

        $conn = $this->em->getConnection();
        $updated = 0;
        $withUseType = 0;

        foreach ($rows as $userId => $data) {
            if ($data['useTypeId'] !== null && $data['useTypeId'] > 0) {
                ++$withUseType;
            }

            if ($dryRun) {
                ++$updated;
                continue;
            }

            $params = [
                'id' => $userId,
                'useTypeId' => $data['useTypeId'],
                'wishedSize' => $data['wishedSize'],
                'usageDuration' => $data['usageDuration'],
                'companyStatus' => $data['companyStatus'],
            ];
            $set = 'useType_id = :useTypeId, wishedSize = :wishedSize, usageDuration = :usageDuration, company_status = :companyStatus';
            if ($data['createdAt'] !== null) {
                $set .= ', created_at = :createdAt';
                $params['createdAt'] = $data['createdAt'];
            }
            $conn->executeStatement(
                'UPDATE fos_user SET '.$set.' WHERE id = :id',
                $params
            );
            ++$updated;
        }

        $dbWithUseType = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM fos_user WHERE useType_id IS NOT NULL AND useType_id > 0'
        );
        $dbWithCreatedAt = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM fos_user WHERE created_at IS NOT NULL'
        );

        $io->title('Restauration profil porteur (fos_user.useType_id, created_at, …)');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Utilisateurs lus dans le dump', (string) count($rows)],
                ['Mises à jour', (string) $updated],
                ['Dont useType_id > 0 dans le dump', (string) $withUseType],
                ['En base avec useType_id > 0', (string) $dbWithUseType],
                ['En base avec created_at renseigné', (string) $dbWithCreatedAt],
                ['Mode', $dryRun ? 'dry-run' : 'écriture'],
            ]
        );

        if ($dryRun) {
            $io->note('Relancez sans --dry-run pour appliquer.');
        } else {
            $io->success('Profils restaurés. Rechargez la page candidats (graphique « type de projet »).');
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array{useTypeId: ?int, wishedSize: ?int, usageDuration: ?int, companyStatus: ?string, createdAt: ?string}>
     */
    private function extractUserRows(string $dumpPath): array
    {
        $rows = [];
        $handle = fopen($dumpPath, 'rb');
        if ($handle === false) {
            return [];
        }

        while (($line = fgets($handle)) !== false) {
            if (!str_contains($line, 'INSERT INTO `fos_user` VALUES')) {
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
                if (!isset($fields[0], $fields[self::USE_TYPE_INDEX])) {
                    $pos = $open + strlen($tuple) + 2;
                    continue;
                }

                $userId = (int) $this->unquoteSqlScalar($fields[0]);
                if ($userId <= 0) {
                    $pos = $open + strlen($tuple) + 2;
                    continue;
                }

                $rows[$userId] = [
                    'createdAt' => $this->nullableDateTime($fields[self::CREATED_AT_INDEX] ?? 'NULL'),
                    'wishedSize' => $this->nullableInt($fields[self::WISHED_SIZE_INDEX] ?? 'NULL'),
                    'usageDuration' => $this->nullableInt($fields[self::USAGE_DURATION_INDEX] ?? 'NULL'),
                    'useTypeId' => $this->nullableInt($fields[self::USE_TYPE_INDEX] ?? 'NULL'),
                    'companyStatus' => $this->nullableString($fields[self::COMPANY_STATUS_INDEX] ?? 'NULL'),
                ];

                $pos = $open + strlen($tuple) + 2;
            }
        }

        fclose($handle);

        return $rows;
    }

    private function nullableDateTime(string $raw): ?string
    {
        $trimmed = trim($raw);
        if (strtoupper($trimmed) === 'NULL' || $trimmed === '') {
            return null;
        }

        $value = $this->unquoteSqlScalar($raw);

        return $value === '' ? null : $value;
    }

    private function nullableInt(string $raw): ?int
    {
        if (strtoupper(trim($raw)) === 'NULL') {
            return null;
        }

        return (int) $this->unquoteSqlScalar($raw);
    }

    private function nullableString(string $raw): ?string
    {
        $trimmed = trim($raw);
        if (strtoupper($trimmed) === 'NULL' || $trimmed === '') {
            return null;
        }

        $value = $this->unquoteSqlScalar($raw);

        return $value === '' ? null : $value;
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

    private function unquoteSqlScalar(string $value): string
    {
        if (strtoupper($value) === 'NULL') {
            return '';
        }

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return str_replace(["\\'", '\\r', '\\n', '\\\\'], ["'", "\r", "\n", '\\'], substr($value, 1, -1));
        }

        return $value;
    }
}
