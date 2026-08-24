<?php

namespace App\Tests\Smoke;

use App\Admin\ApplicationAdmin;
use App\Tests\Support\AdminWebTestCase;
use Sonata\AdminBundle\Admin\Pool;

class SonataAdminCandidatureListTest extends AdminWebTestCase
{
    public function testCandidatureListRendersWithAutocompleteFilters(): void
    {
        $client = $this->createAuthenticatedAdminClient();
        $client->request('GET', '/admin/candidature/list');

        $this->assertSuccessfulHtmlResponse($client, '/admin/candidature/list');
        $this->assertSelectorExists('form', '/admin/candidature/list');

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('sonata_type_model_autocomplete', $content);
        $this->assertStringContainsString('Rechercher un porteur', $content);
        $this->assertStringContainsString('Rechercher un espace', $content);
    }

    public function testCandidatureListPaginationDoesNotAllow250Rows(): void
    {
        $client = $this->createAuthenticatedAdminClient();
        $pool = static::getContainer()->get('sonata.admin.pool');
        $this->assertInstanceOf(Pool::class, $pool);

        $admin = $pool->getAdminByAdminCode('app.admin.application');
        $this->assertInstanceOf(ApplicationAdmin::class, $admin);
        $this->assertSame([10, 25, 50, 100], $admin->getPerPageOptions());
        $this->assertFalse($admin->determinedPerPageValue(250));

        $client->request('GET', '/admin/candidature/list?filter[_per_page]=250');
        $this->assertSuccessfulHtmlResponse($client, '/admin/candidature/list?filter[_per_page]=250');
    }

    public function testCandidatureCreateFormUsesAutocompleteForProjectHolder(): void
    {
        $client = $this->createAuthenticatedAdminClient();
        $client->request('GET', '/admin/candidature/create');

        $this->assertSuccessfulHtmlResponse($client, '/admin/candidature/create');

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('sonata_type_model_autocomplete', $content);
        $this->assertStringContainsString('Rechercher un porteur', $content);
    }
}
