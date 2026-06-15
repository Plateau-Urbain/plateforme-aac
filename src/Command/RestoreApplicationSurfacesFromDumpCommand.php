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
 * Restaure wishedSize, openToGlobalProject et devenirSocietaire sur Application
 * depuis un dump SQL prod (schéma dump 2026-05 : 17 colonnes par ligne INSERT).
 */
#[AsCommand(
    name: 'app:restore-application-surfaces-from-dump',
    description: 'Restaure les surfaces candidature (wishedSize, etc.) depuis plateforme_prod-*.sql',
)]
class RestoreApplicationSurfacesFromDumpCommand extends Command
{
  private const WISHED_SIZE_INDEX = 13;
  private const OPEN_TO_GLOBAL_INDEX = 14;
  private const DEVENIR_SOCIETAIRE_INDEX = 16;

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

    $rows = $this->extractSurfaceRows($dumpPath);
    if ($rows === []) {
      $io->warning('Aucune ligne Application trouvée dans le dump.');

      return Command::FAILURE;
    }

    $conn = $this->em->getConnection();
    $updated = 0;
    $withSurface = 0;

    foreach ($rows as $applicationId => $data) {
      if ($data['wishedSize'] !== null && $data['wishedSize'] > 0) {
        ++$withSurface;
      }

      if ($dryRun) {
        ++$updated;
        continue;
      }

      $conn->executeStatement(
        'UPDATE Application SET wishedSize = :wishedSize, openToGlobalProject = :openToGlobal, devenirSocietaire = :devenirSocietaire WHERE id = :id',
        [
          'id' => $applicationId,
          'wishedSize' => $data['wishedSize'],
          'openToGlobal' => $data['openToGlobal'] === null ? null : ($data['openToGlobal'] ? 1 : 0),
          'devenirSocietaire' => $data['devenirSocietaire'] === null ? null : ($data['devenirSocietaire'] ? 1 : 0),
        ]
      );
      ++$updated;
    }

    $dbWithSurface = (int) $conn->fetchOne(
      'SELECT COUNT(*) FROM Application WHERE wishedSize IS NOT NULL AND wishedSize > 0'
    );

    $io->title('Restauration surfaces candidature (Application.wishedSize)');
    $io->table(
      ['Métrique', 'Valeur'],
      [
        ['Lignes lues dans le dump', (string) count($rows)],
        ['Candidatures traitées', (string) $updated],
        ['Dont surface > 0 dans le dump', (string) $withSurface],
        ['En base avec wishedSize > 0', (string) $dbWithSurface],
        ['Mode', $dryRun ? 'dry-run' : 'écriture'],
      ]
    );

    if ($dryRun) {
      $io->note('Relancez sans --dry-run pour appliquer les UPDATE.');
    } else {
      $io->success('Surfaces restaurées. Rechargez la page candidats de l\'AAC.');
    }

    return Command::SUCCESS;
  }

  /**
   * @return array<int, array{wishedSize: ?int, openToGlobal: ?bool, devenirSocietaire: ?bool}>
   */
  private function extractSurfaceRows(string $dumpPath): array
  {
    $rows = [];
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
        if (!isset($fields[0], $fields[self::WISHED_SIZE_INDEX])) {
          $pos = $open + strlen($tuple) + 2;
          continue;
        }

        $applicationId = (int) $this->unquoteSqlScalar($fields[0]);
        if ($applicationId <= 0) {
          $pos = $open + strlen($tuple) + 2;
          continue;
        }

        $rows[$applicationId] = [
          'wishedSize' => $this->nullableInt($fields[self::WISHED_SIZE_INDEX]),
          'openToGlobal' => $this->nullableBool($fields[self::OPEN_TO_GLOBAL_INDEX] ?? 'NULL'),
          'devenirSocietaire' => $this->nullableBool($fields[self::DEVENIR_SOCIETAIRE_INDEX] ?? 'NULL'),
        ];

        $pos = $open + strlen($tuple) + 2;
      }
    }

    fclose($handle);

    return $rows;
  }

  private function nullableInt(string $raw): ?int
  {
    if (strtoupper(trim($raw)) === 'NULL') {
      return null;
    }

    return (int) $this->unquoteSqlScalar($raw);
  }

  private function nullableBool(string $raw): ?bool
  {
    $trimmed = strtoupper(trim($raw));
    if ($trimmed === 'NULL' || $trimmed === '') {
      return null;
    }

    return (bool) (int) $this->unquoteSqlScalar($raw);
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
