<?php

namespace App\Tests\Smoke;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait AuthenticatedOwnerClientTrait
{
    private const OWNER_TEST_EMAIL = 'owner-smoke-test@plateau-urbain.test';

    protected function createAuthenticatedOwnerClient(): KernelBrowser
    {
        $client = static::createClient();
        $owner = $this->ensureOwnerTestUserExists();
        $client->loginUser($owner);

        return $client;
    }

    private function ensureOwnerTestUserExists(): User
    {
        $container = static::getContainer();
        $repository = $container->get(UserRepository::class);
        $existing = $repository->findOneBy(['email' => self::OWNER_TEST_EMAIL]);
        if ($existing instanceof User) {
            return $existing;
        }

        $hasher = $container->get(UserPasswordHasherInterface::class);
        $em = $container->get(EntityManagerInterface::class);

        $owner = new User();
        $owner->setEmail(self::OWNER_TEST_EMAIL);
        $owner->setEmailCanonical(self::OWNER_TEST_EMAIL);
        $owner->setEnabled(true);
        $owner->setTypeUser(User::PROPRIO);
        $owner->setRoles(['ROLE_OWNER', 'ROLE_USER']);
        $owner->setPassword($hasher->hashPassword($owner, 'TestOwnerPassword1!'));
        $owner->setCivility(User::MISTER);
        $owner->setFirstname('Test');
        $owner->setLastname('Owner');
        $owner->setCompany('Test Company');
        $owner->setCompanyStatus('SAS');
        $owner->setAddress('123 Test St');
        $owner->setZipcode('75001');
        $owner->setCity('Paris');
        $owner->setCreatedAt(new \DateTime());

        $em->persist($owner);
        $em->flush();

        return $owner;
    }
}
