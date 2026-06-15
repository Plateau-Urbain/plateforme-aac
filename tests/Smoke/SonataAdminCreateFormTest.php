<?php

namespace App\Tests\Smoke;

use App\Tests\Support\AdminWebTestCase;

/**
 * Les formulaires de création Sonata doivent se compiler (types Form SF6, pas de crash Twig).
 */
class SonataAdminCreateFormTest extends AdminWebTestCase
{
    /**
     * @return iterable<string, array{0: string}>
     */
    public static function adminCreateRoutesProvider(): iterable
    {
        yield 'espace (property)' => ['/admin/property/create'];
        yield 'actualité' => ['/admin/app/actuality/create'];
        yield 'utilisateur' => ['/admin/utilisateurs/create'];
        yield 'candidature' => ['/admin/candidature/create'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('adminCreateRoutesProvider')]
    public function testAdminCreateFormRenders(string $path): void
    {
        $client = $this->createAuthenticatedAdminClient();
        $client->request('GET', $path);

        $this->assertSuccessfulHtmlResponse($client, $path);
        $this->assertSelectorExists('form', $path);
    }
}
