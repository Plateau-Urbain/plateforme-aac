<?php

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SpaceManagerAddPhotoTest extends WebTestCase
{
    use AuthenticatedOwnerClientTrait;

    public function testAddPhotoOnNewSpacePersistsImage(): void
    {
        $client = $this->createAuthenticatedOwnerClient();
        $crawler = $client->request('GET', '/espace-manager/ajouter');

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $form = $crawler->filter('form')->form();
        $fileFields = $crawler->filter('input[type="file"]');
        $this->assertGreaterThan(0, $fileFields->count(), 'Aucun champ fichier trouvé dans le HTML');

        $photoFieldName = null;
        foreach ($fileFields as $fileField) {
            $name = $fileField->getAttribute('name');
            if (str_contains($name, '[pics]')) {
                $photoFieldName = $name;
                break;
            }
        }
        $this->assertNotNull($photoFieldName, 'Champ fichier pics introuvable. Noms trouvés: ' . implode(', ', array_map(
            static fn (\DOMElement $node): string => $node->getAttribute('name'),
            iterator_to_array($fileFields)
        )));

        $tmp = tempnam(sys_get_temp_dir(), 'space_photo_');
        copy(__DIR__ . '/../../public/images/groupe_ajouter_espace.png', $tmp);
        $uploaded = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

        $form['appbundle_space[name]'] = 'Espace photo test';
        /** @var FileFormField $photoField */
        $photoField = $form[$photoFieldName];
        $photoField->upload($uploaded);

        $values = $form->getPhpValues();
        $files = $form->getPhpFiles();
        $values['add_photo'] = '1';

        $client->request($form->getMethod(), $form->getUri(), $values, $files);
        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect(), 'Réponse inattendue: ' . $response->getContent());
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/espace-manager/editer/', $location, 'La photo aurait dû créer l\'espace et rediriger vers l\'édition');

        $client->followRedirect();
        $this->assertStringNotContainsString(
            'Veuillez sélectionner une photo à ajouter.',
            (string) $client->getResponse()->getContent()
        );
    }
}
