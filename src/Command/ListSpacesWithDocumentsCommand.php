<?php

namespace App\Command;

use App\Entity\Space;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-spaces-with-documents',
    description: 'List spaces that have required documents',
)]
class ListSpacesWithDocumentsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $spaces = $this->em->getRepository(Space::class)->findAll();

        $io->title('Espaces avec documents obligatoires');

        $spacesWithDocs = [];
        foreach ($spaces as $space) {
            $documents = $space->getDocuments();
            if (count($documents) > 0) {
                $spacesWithDocs[] = [
                    $space->getId(),
                    $space->getName(),
                    count($documents),
                    $space->isEnabled() ? 'OUI' : 'NON',
                    $space->isClosed()  ? 'OUI' : 'NON',
                ];
            }
        }

        if (empty($spacesWithDocs)) {
            $io->warning('Aucun espace avec des documents obligatoires trouvé');

            return Command::SUCCESS;
        }

        $io->table(['ID', 'Nom', 'Documents', 'Publié', 'Fermé'], $spacesWithDocs);

        return Command::SUCCESS;
    }
}
