<?php

namespace App\Tests\Integration;

use App\Entity\Application;
use App\Entity\Space;
use App\Entity\User;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Tests d'intégration ApplicationRepository.
 * Vérifie les requêtes critiques : filter, navigation, candidatures par propriétaire.
 */
class ApplicationRepositoryTest extends AbstractDoctrineTestCase
{
    private ApplicationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository(Application::class);
    }

    // --- Fixtures ---

    private function makeUser(int $type = User::PORTEUR): User
    {
        $user = new User();
        $user->setEmail('user-repo-' . uniqid('', true) . '@test.test');
        $user->setEmailCanonical($user->getEmail());
        $user->setEnabled(true);
        $user->setTypeUser($type);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('hashed');
        $user->setCreatedAt(new \DateTime());
        $this->entityManager->persist($user);
        return $user;
    }

    private function makeSpace(User $owner): Space
    {
        $space = new Space();
        $space->setName('Espace ' . uniqid('', true));
        $space->setDescription('Description');
        $space->setActivityDescription('Activité');
        $space->setOwner($owner);
        $this->entityManager->persist($space);
        return $space;
    }

    private function makeApplication(Space $space, User $applicant, string $status = 'awaiting'): Application
    {
        $app = new Application();
        $app->setSpace($space);
        $app->setProjectHolder($applicant);
        $app->setStatus($status);
        $this->entityManager->persist($app);
        return $app;
    }

    // --- filter() ---

    public function testFilterExcludesDraftApplications(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $applicant = $this->makeUser();
        $space = $this->makeSpace($owner);

        $sent = $this->makeApplication($space, $applicant, 'awaiting');
        $draft = $this->makeApplication($space, $applicant, 'draft');
        $this->entityManager->flush();

        $qb = $this->repository->filter(['space' => $space]);
        $results = $qb->getQuery()->getResult();

        $ids = array_map(fn(Application $a) => $a->getId(), $results);
        $this->assertContains($sent->getId(), $ids, 'Application envoyée doit figurer dans filter()');
        $this->assertNotContains($draft->getId(), $ids, 'Brouillon exclu de filter()');
    }

    public function testFilterBySpaceReturnsOnlyThatSpacesApplications(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $applicant = $this->makeUser();
        $space1 = $this->makeSpace($owner);
        $space2 = $this->makeSpace($owner);

        $app1 = $this->makeApplication($space1, $applicant, 'awaiting');
        $app2 = $this->makeApplication($space2, $applicant, 'awaiting');
        $this->entityManager->flush();

        $results = $this->repository->filter(['space' => $space1])->getQuery()->getResult();

        $ids = array_map(fn(Application $a) => $a->getId(), $results);
        $this->assertContains($app1->getId(), $ids);
        $this->assertNotContains($app2->getId(), $ids);
    }

    public function testFilterByStatusReturnsOnlyMatchingApplications(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $applicant = $this->makeUser();
        $space = $this->makeSpace($owner);

        $accepted = $this->makeApplication($space, $applicant, Application::ACCEPT_STATUS);
        $rejected = $this->makeApplication($space, $applicant, Application::REJECT_STATUS);
        $this->entityManager->flush();

        $results = $this->repository->filter([
            'space' => $space,
            'status' => Application::ACCEPT_STATUS,
        ])->getQuery()->getResult();

        $ids = array_map(fn(Application $a) => $a->getId(), $results);
        $this->assertContains($accepted->getId(), $ids);
        $this->assertNotContains($rejected->getId(), $ids);
    }

    public function testFilterReturnsQueryBuilder(): void
    {
        $qb = $this->repository->filter([]);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    // --- getApplicationPerOwner() ---

    public function testGetApplicationPerOwnerReturnsOnlyOwnerApplications(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $otherOwner = $this->makeUser(User::PROPRIO);
        $applicant = $this->makeUser();

        $mySpace = $this->makeSpace($owner);
        $otherSpace = $this->makeSpace($otherOwner);

        $myApp = $this->makeApplication($mySpace, $applicant, 'awaiting');
        $otherApp = $this->makeApplication($otherSpace, $applicant, 'awaiting');
        $this->entityManager->flush();

        $results = $this->repository->getApplicationPerOwner($owner);

        $ids = array_map(fn(Application $a) => $a->getId(), $results);
        $this->assertContains($myApp->getId(), $ids);
        $this->assertNotContains($otherApp->getId(), $ids);
    }

    // --- Navigation (next/prev) ---

    public function testGetNextApplicationReturnsHigherIdOnSameSpace(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $space = $this->makeSpace($owner);
        $applicant1 = $this->makeUser();
        $applicant2 = $this->makeUser();

        $app1 = $this->makeApplication($space, $applicant1, 'awaiting');
        $app2 = $this->makeApplication($space, $applicant2, 'awaiting');
        $this->entityManager->flush();

        $next = $this->repository->getNextApplication($app1);

        $this->assertNotNull($next);
        $this->assertSame($app2->getId(), $next->getId());
    }

    public function testGetPrevApplicationReturnsLowerIdOnSameSpace(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $space = $this->makeSpace($owner);
        $applicant1 = $this->makeUser();
        $applicant2 = $this->makeUser();

        $app1 = $this->makeApplication($space, $applicant1, 'awaiting');
        $app2 = $this->makeApplication($space, $applicant2, 'awaiting');
        $this->entityManager->flush();

        $prev = $this->repository->getPrevApplication($app2);

        $this->assertNotNull($prev);
        $this->assertSame($app1->getId(), $prev->getId());
    }

    public function testGetNextApplicationReturnsNullForLastApplication(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $space = $this->makeSpace($owner);
        $app = $this->makeApplication($space, $this->makeUser(), 'awaiting');
        $this->entityManager->flush();

        $next = $this->repository->getNextApplication($app);

        $this->assertNull($next);
    }

    public function testGetPrevApplicationIgnoresDraftStatus(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $space = $this->makeSpace($owner);
        $applicant = $this->makeUser();

        $this->makeApplication($space, $applicant, 'draft'); // persiste un brouillon ignoré
        $awaiting = $this->makeApplication($space, $applicant, 'awaiting');
        $this->entityManager->flush();

        // draft ne doit pas figurer dans la navigation
        $prev = $this->repository->getPrevApplication($awaiting);
        $this->assertNull($prev, 'getPrevApplication doit ignorer les brouillons');
    }

    // --- getApplicantNext/Prev ---

    public function testGetApplicantNextApplicationReturnsNextForSameApplicant(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $applicant = $this->makeUser();
        $space1 = $this->makeSpace($owner);
        $space2 = $this->makeSpace($owner);

        $app1 = $this->makeApplication($space1, $applicant, 'awaiting');
        $app2 = $this->makeApplication($space2, $applicant, 'awaiting');
        $this->entityManager->flush();

        $next = $this->repository->getApplicantNextApplication($app1, $applicant);

        $this->assertNotNull($next);
        $this->assertSame($app2->getId(), $next->getId());
    }

    public function testGetApplicantPrevApplicationReturnsNullForFirstApplication(): void
    {
        $owner = $this->makeUser(User::PROPRIO);
        $applicant = $this->makeUser();
        $space = $this->makeSpace($owner);

        $app = $this->makeApplication($space, $applicant, 'awaiting');
        $this->entityManager->flush();

        $prev = $this->repository->getApplicantPrevApplication($app, $applicant);
        $this->assertNull($prev);
    }
}
