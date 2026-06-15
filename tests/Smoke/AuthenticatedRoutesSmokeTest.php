<?php

namespace App\Tests\Smoke;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticatedRoutesSmokeTest extends WebTestCase
{
    private const OWNER_TEST_EMAIL = 'owner-smoke-test@plateau-urbain.test';
    private const PORTEUR_TEST_EMAIL = 'porteur-smoke-test@plateau-urbain.test';

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

    protected function createAuthenticatedPorteurClient(): KernelBrowser
    {
        $client = static::createClient();
        $porteur = $this->ensurePorteurTestUserExists();
        $client->loginUser($porteur);

        return $client;
    }

    private function ensurePorteurTestUserExists(): User
    {
        $container = static::getContainer();
        $repository = $container->get(UserRepository::class);
        $existing = $repository->findOneBy(['email' => self::PORTEUR_TEST_EMAIL]);
        if ($existing instanceof User) {
            return $existing;
        }

        $hasher = $container->get(UserPasswordHasherInterface::class);
        $em = $container->get(EntityManagerInterface::class);

        $porteur = new User();
        $porteur->setEmail(self::PORTEUR_TEST_EMAIL);
        $porteur->setEmailCanonical(self::PORTEUR_TEST_EMAIL);
        $porteur->setEnabled(true);
        $porteur->setTypeUser(User::PORTEUR);
        $porteur->setRoles(['ROLE_USER']);
        $porteur->setPassword($hasher->hashPassword($porteur, 'TestPorteurPassword1!'));
        $porteur->setCivility(User::MISTER);
        $porteur->setFirstname('Test');
        $porteur->setLastname('Porteur');
        $porteur->setCreatedAt(new \DateTime());

        $em->persist($porteur);
        $em->flush();

        return $porteur;
    }

    public function testEspaceManagerListLoadsSuccessfully(): void
    {
        $client = $this->createAuthenticatedOwnerClient();
        $client->request('GET', '/espace-manager/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testEspaceManagerAddLoadsSuccessfully(): void
    {
        $client = $this->createAuthenticatedOwnerClient();
        $client->request('GET', '/espace-manager/ajouter');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testDocumentDeleteReturns403OnInvalidCsrf(): void
    {
        $client = $this->createAuthenticatedOwnerClient();
        $client->request('POST', '/espaces/file/123/delete', [
            '_token' => 'invalid_csrf_token'
        ]);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testDocumentDeleteReturns404OnValidCsrfButMissingFile(): void
    {
        $client = $this->createAuthenticatedOwnerClient();
        
        // Trigger a request to initialize the session and request context on the client
        $client->request('GET', '/espace-manager/');
        $request = $client->getRequest();
        $session = $request->getSession();
        
        // Push the client's request to the client's RequestStack to make session available
        $requestStack = $client->getContainer()->get(RequestStack::class);
        $requestStack->push($request);
        
        // Get CSRF token manager from the client's container
        $csrfTokenManager = $client->getContainer()->get(\Symfony\Component\Security\Csrf\CsrfTokenManagerInterface::class);
        $token = $csrfTokenManager->getToken('remove_file_99999')->getValue();

        // Save session changes
        $session->save();

        // Pop the request to clean up
        $requestStack->pop();

        $client->request('POST', '/espaces/file/99999/delete', [
            '_token' => $token
        ]);

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testPasswordResetFormWithInvalidTokenRedirects(): void
    {
        $client = static::createClient();
        $client->request('GET', '/resetting/reset/invalid-token-12345');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->has('Location'));
        $this->assertStringContainsString('/resetting/request', $client->getResponse()->headers->get('Location') ?? '');
    }

    public function testPasswordResetSendEmailWithInvalidCsrfRedirects(): void
    {
        $client = static::createClient();
        $client->request('POST', '/resetting/send-email', [
            '_token' => 'invalid_csrf_token',
            'username' => 'test@plateau-urbain.test'
        ]);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->headers->has('Location'));
        $this->assertStringContainsString('/resetting/request', $client->getResponse()->headers->get('Location') ?? '');
    }

    // --- Profil propriétaire ---

    public function testProfilPageRedirectsAuthenticatedOwnerToRoleRoute(): void
    {
        $client = $this->createAuthenticatedOwnerClient();
        $client->request('GET', '/profil');

        // /profil redirige vers /profil/proprio ou /profil/candidat selon le type
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 302]);
        if ($client->getResponse()->getStatusCode() === 302) {
            $location = $client->getResponse()->headers->get('Location') ?? '';
            $this->assertStringContainsString('/profil/', $location);
        }
    }

    public function testProfilProprioPageLoadsForOwner(): void
    {
        $client = $this->createAuthenticatedOwnerClient();
        $client->request('GET', '/profil/proprio');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testProfilCandidatPageLoadsForPorteur(): void
    {
        $client = $this->createAuthenticatedPorteurClient();
        $client->request('GET', '/profil/candidat');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    // --- Candidatures (porteur) ---

    public function testMyApplicationsListLoadsForPorteur(): void
    {
        $client = $this->createAuthenticatedPorteurClient();
        $client->request('GET', '/mes-candidatures');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testUnauthenticatedUserRedirectedFromProfile(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profil');

        // Non authentifié → redirection vers login
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $location = $client->getResponse()->headers->get('Location') ?? '';
        $this->assertStringContainsString('login', $location);
    }

    public function testUnauthenticatedUserRedirectedFromMyCandidatures(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mes-candidatures');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
    }

    // --- Routes publiques non encore couvertes ---

    public function testActualityShowRespondsWithoutCrash(): void
    {
        $client = static::createClient();
        $client->request('GET', '/slider/show');

        $this->assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }

    public function testAacListRespondsWithoutCrash(): void
    {
        $client = static::createClient();
        $client->request('GET', '/appels-a-candidature/list');

        $this->assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }

    public function testResettingCheckEmailRespondsWithoutCrash(): void
    {
        // /resetting/check-email est accessible directement (peut rediriger si pas de session)
        $client = static::createClient();
        $client->request('GET', '/resetting/check-email');

        $this->assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }
}
