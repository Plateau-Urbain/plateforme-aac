<?php

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Les pages publiques critiques de la migration doivent répondre sans exception (200 ou 302).
 */
class HttpRoutesSmokeTest extends WebTestCase
{
    /**
     * @return iterable<string, array{0: string, 1: list<int>}>
     */
    public static function publicRoutesProvider(): iterable
    {
        yield 'accueil' => ['/', [200, 302]];
        yield 'cgu' => ['/cgu', [200]];
        yield 'proprietaire' => ['/proprietaire', [200]];
        yield 'recherche' => ['/recherche/', [200]];
        yield 'login' => ['/login', [200]];
        yield 'inscription' => ['/register/', [200]];
        yield 'reset mot de passe' => ['/resetting/request', [200]];
        yield 'mot de passe oublie alias' => ['/mot-de-passe-oublie', [302]];
        yield 'candidatures (auth)' => ['/candidatures/', [302]];
        yield 'espace manager (auth)' => ['/espace-manager/', [302]];
    }

    /**
     * @param list<int> $expectedStatuses
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('publicRoutesProvider')]
    public function testPublicRouteRespondsWithoutCrash(string $path, array $expectedStatuses): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        $this->assertContains(
            $client->getResponse()->getStatusCode(),
            $expectedStatuses,
            sprintf('Route %s a renvoyé %d', $path, $client->getResponse()->getStatusCode())
        );
    }
}
