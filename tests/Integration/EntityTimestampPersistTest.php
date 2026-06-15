<?php

namespace App\Tests\Integration;

use App\Entity\Actuality;
use App\Entity\Application;
use App\Entity\Space;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Vérifie que les dates obligatoires (ex-Gedmo Timestampable) sont renseignées à la création.
 */
class EntityTimestampPersistTest extends AbstractDoctrineTestCase
{
    public function testSpaceCreatedAndUpdatedAreSetOnPersist(): void
    {
        $space = new Space();
        $space->setName('Test migration SF6');
        $space->setDescription('Description test');
        $space->setActivityDescription('Activité test');

        $this->entityManager->persist($space);
        $this->entityManager->flush();

        $this->assertInstanceOf(\DateTimeInterface::class, $space->getCreated());
        $this->assertInstanceOf(\DateTimeInterface::class, $space->getUpdated());
    }

    public function testApplicationCreatedIsSetOnPersist(): void
    {
        $application = new Application();

        $this->entityManager->persist($application);
        $this->entityManager->flush();

        $this->assertInstanceOf(\DateTimeInterface::class, $application->getCreated());
    }

    public function testActualityUpdatedAtIsSetOnPersist(): void
    {
        $actuality = new Actuality();
        $actuality->setTitle('Actualité test migration');

        $this->entityManager->persist($actuality);
        $this->entityManager->flush();

        $this->assertInstanceOf(\DateTimeInterface::class, $actuality->getUpdatedAt());
    }

    public function testUserCreatedAtIsSetOnPersist(): void
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('migration-test-'.uniqid('', true).'@example.test');
        $user->setEnabled(true);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, 'TestPassword1!'));
        $user->setCreatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
    }
}
