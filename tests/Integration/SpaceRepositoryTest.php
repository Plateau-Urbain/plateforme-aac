<?php

namespace App\Tests\Integration;

use App\Entity\Space;
use App\Entity\User;
use App\Repository\SpaceRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Tests d'intégration SpaceRepository.
 * Vérifie que les méthodes de requête critiques survivent à la migration SF3.4 → SF6.
 */
class SpaceRepositoryTest extends AbstractDoctrineTestCase
{
    private SpaceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository(Space::class);
    }

    // --- Fixtures ---

    private function makeSpace(array $overrides = []): Space
    {
        $space = new Space();
        $space->setName($overrides['name'] ?? 'Espace Test ' . uniqid('', true));
        $space->setDescription('Description');
        $space->setActivityDescription('Activité');
        $space->setEnabled($overrides['enabled'] ?? false);
        $space->setClosed($overrides['closed'] ?? false);
        if (isset($overrides['submitted'])) {
            $space->setSubmitted($overrides['submitted']);
        }
        if (isset($overrides['owner'])) {
            $space->setOwner($overrides['owner']);
        }
        if (isset($overrides['limitAvailability'])) {
            $space->setLimitAvailability($overrides['limitAvailability']);
        }

        $this->entityManager->persist($space);
        return $space;
    }

    private function makeOwner(): User
    {
        $user = new User();
        $user->setEmail('owner-repo-' . uniqid('', true) . '@test.test');
        $user->setEmailCanonical($user->getEmail());
        $user->setEnabled(true);
        $user->setTypeUser(User::PROPRIO);
        $user->setRoles(['ROLE_OWNER']);
        $user->setPassword('hashed');
        $user->setCreatedAt(new \DateTime());
        $this->entityManager->persist($user);
        return $user;
    }

    // --- filter() ---

    public function testFilterWithNoParamsReturnsQueryResult(): void
    {
        $this->makeSpace(['name' => 'Espace filter A']);
        $this->makeSpace(['name' => 'Espace filter B']);
        $this->entityManager->flush();

        $results = $this->repository->filter([]);

        $this->assertIsArray($results);
        // Les deux espaces créés doivent figurer dans les résultats
        $names = array_map(fn(Space $s) => $s->getName(), $results);
        $this->assertContains('Espace filter A', $names);
        $this->assertContains('Espace filter B', $names);
    }

    public function testFilterByOwnerReturnsOnlyOwnedSpaces(): void
    {
        $owner = $this->makeOwner();
        $other = $this->makeOwner();

        $mine = $this->makeSpace(['name' => 'Mon espace', 'owner' => $owner]);
        $notMine = $this->makeSpace(['name' => 'Autre espace', 'owner' => $other]);
        $this->entityManager->flush();

        $results = $this->repository->filter(['user' => $owner]);

        $ids = array_map(fn(Space $s) => $s->getId(), $results);
        $this->assertContains($mine->getId(), $ids);
        $this->assertNotContains($notMine->getId(), $ids);
    }

    public function testFilterEnabledReturnsOnlyPublishedActiveSpaces(): void
    {
        $future = (new \DateTime('today'))->modify('+30 days');

        $enabled = $this->makeSpace([
            'name' => 'Espace publié',
            'enabled' => true,
            'submitted' => true,
            'closed' => false,
            'limitAvailability' => $future,
        ]);
        $disabled = $this->makeSpace(['name' => 'Espace non publié', 'enabled' => false]);
        $this->entityManager->flush();

        $results = $this->repository->filter(['enabled' => true]);

        $ids = array_map(fn(Space $s) => $s->getId(), $results);
        $this->assertContains($enabled->getId(), $ids);
        $this->assertNotContains($disabled->getId(), $ids);
    }

    public function testFilterWithPaginationReturnsPaginatorInstance(): void
    {
        $this->makeSpace(['name' => 'Paginated A']);
        $this->makeSpace(['name' => 'Paginated B']);
        $this->entityManager->flush();

        $result = $this->repository->filter(['pagination' => 10]);

        $this->assertInstanceOf(Paginator::class, $result);
    }

    // --- findAllEnabled() ---

    public function testFindAllEnabledReturnsOnlyActiveNonClosedSpaces(): void
    {
        $enabled = $this->makeSpace(['name' => 'Actif', 'enabled' => true, 'closed' => false]);
        $disabled = $this->makeSpace(['name' => 'Désactivé', 'enabled' => false, 'closed' => false]);
        $closed = $this->makeSpace(['name' => 'Fermé', 'enabled' => true, 'closed' => true]);
        $this->entityManager->flush();

        $results = $this->repository->findAllEnabled();

        $ids = array_map(fn(Space $s) => $s->getId(), $results);
        $this->assertContains($enabled->getId(), $ids);
        $this->assertNotContains($disabled->getId(), $ids);
        $this->assertNotContains($closed->getId(), $ids);
    }
}
