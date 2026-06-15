<?php

namespace App\Tests\Support;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AdminWebTestCase extends WebTestCase
{
    private const ADMIN_TEST_EMAIL = 'admin-migration-test@plateau-urbain.test';

    protected function createAuthenticatedAdminClient(): KernelBrowser
    {
        $client = static::createClient();
        $admin = $this->ensureAdminTestUserExists();
        $client->loginUser($admin);

        return $client;
    }

    private function ensureAdminTestUserExists(): User
    {
        $container = static::getContainer();
        $repository = $container->get(UserRepository::class);
        $existing = $repository->findOneBy(['email' => self::ADMIN_TEST_EMAIL]);
        if ($existing instanceof User) {
            return $existing;
        }

        $hasher = $container->get(UserPasswordHasherInterface::class);
        $em = $container->get(EntityManagerInterface::class);

        $admin = new User();
        $admin->setEmail(self::ADMIN_TEST_EMAIL);
        $admin->setEmailCanonical(self::ADMIN_TEST_EMAIL);
        $admin->setEnabled(true);
        $admin->setTypeUser(User::ADMIN);
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_SONATA_ADMIN']);
        $admin->setPassword($hasher->hashPassword($admin, 'TestAdminPassword1!'));
        $admin->setCreatedAt(new \DateTime());

        $em->persist($admin);
        $em->flush();

        return $admin;
    }

    protected function assertSuccessfulHtmlResponse(KernelBrowser $client, string $routeLabel): void
    {
        $status = $client->getResponse()->getStatusCode();
        $this->assertSame(
            200,
            $status,
            sprintf('La route admin "%s" devrait répondre 200, reçu %d', $routeLabel, $status)
        );

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringNotContainsString('Could not load type', $content, $routeLabel);
        $this->assertStringNotContainsString('InvalidArgumentException', $content, $routeLabel);
        $this->assertStringNotContainsString('ModelManagerException', $content, $routeLabel);
    }
}
