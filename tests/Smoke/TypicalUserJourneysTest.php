<?php

namespace App\Tests\Smoke;

use App\Entity\Application;
use App\Entity\Category;
use App\Entity\Space;
use App\Entity\SpaceType;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class TypicalUserJourneysTest extends WebTestCase
{
    use AuthenticatedOwnerClientTrait;
    use AuthenticatedPorteurClientTrait;

    /**
     * Parcours 1 : Inscription, gestion des erreurs et activation du compte
     */
    public function testJourney1RegistrationAndErrorManagement(): void
    {
        $client = static::createClient();

        // 1. Aller sur la page d'inscription
        $crawler = $client->request('GET', '/register/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // 2. Tenter une inscription avec un e-mail invalide
        $form = $crawler->filter('form')->form();
        $form['project_holder_user_registration[email]'] = 'invalide-email-format';
        $form['project_holder_user_registration[plainPassword][first]'] = 'ShortPassword1!';
        $form['project_holder_user_registration[plainPassword][second]'] = 'ShortPassword1!';
        $form['project_holder_user_registration[captcha]'] = 'TEST_BYPASS'; // bypass code configuré pour le test

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $redirectUrl = (string) $client->getResponse()->headers->get('Location');
        $this->assertSame('/', $redirectUrl);

        // 3. Inscription valide
        $crawler = $client->request('GET', '/register/');
        $form = $crawler->filter('form')->form();
        
        $uniqueEmail = 'journey-test-' . uniqid() . '@plateau-urbain.test';
        $form['project_holder_user_registration[email]'] = $uniqueEmail;
        $form['project_holder_user_registration[plainPassword][first]'] = 'PasswordValide123!';
        $form['project_holder_user_registration[plainPassword][second]'] = 'PasswordValide123!';
        $form['project_holder_user_registration[captcha]'] = 'TEST_BYPASS';

        $client->submit($form);

        // Redirection vers l'attente de confirmation par e-mail
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/register/check-email', (string) $client->getResponse()->headers->get('Location'));

        // Récupérer l'utilisateur créé en base
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $userRepo = $container->get(UserRepository::class);
        
        $user = $userRepo->findOneBy(['email' => $uniqueEmail]);
        $this->assertNotNull($user);
        $this->assertFalse($user->isEnabled());
        $this->assertNotNull($user->getConfirmationToken());

        // 4. Activation du compte
        $client->request('GET', '/register/confirm/' . $user->getConfirmationToken());
        $this->assertTrue($client->getResponse()->isRedirect());

        // Récupérer l'utilisateur à nouveau pour vérifier sa mise à jour
        $clientEm = $client->getContainer()->get(EntityManagerInterface::class);
        $user = $clientEm->getRepository(User::class)->findOneBy(['email' => $uniqueEmail]);

        $this->assertTrue($user->isEnabled());
        $this->assertNull($user->getConfirmationToken());
    }

    /**
     * Parcours 2 & 3 : Création d'espace, Candidature, Validation et Export
     */
    public function testJourney2And3SpaceCreationApplicationValidationAndExport(): void
    {
        $client = static::createClient();

        // On s'assure d'avoir les utilisateurs de test en base
        $owner = $this->ensureOwnerTestUserExists();
        $porteur = $this->ensurePorteurJourneyUserExists();
        
        $missing = $porteur->getMissingProfileFields();
        $this->assertEmpty($missing, 'Profil candidat incomplet. Champs manquants: ' . implode(', ', $missing));
        
        $spaceType = $this->ensureSpaceTypeExists();
        $category = $this->ensureCategoryExists();

        // -------------------------------------------------------------
        // PARCOURS 2 : Création d'espace par le Propriétaire
        // -------------------------------------------------------------
        $client->loginUser($owner);

        $crawler = $client->request('GET', '/espace-manager/ajouter');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $form = $crawler->filter('form')->form();
        $spaceName = 'Espace Journey ' . uniqid();
        
        $form['appbundle_space[name]'] = $spaceName;
        $form['appbundle_space[type]'] = (string) $spaceType->getId();
        $form['appbundle_space[zipCode]'] = '75001';
        $form['appbundle_space[city]'] = 'Paris';
        $form['appbundle_space[availability]'] = '12 mois';
        $form['appbundle_space[nbSpaces]'] = '5';
        $form['appbundle_space[minSpace]'] = '10';
        $form['appbundle_space[maxSpace]'] = '100';
        $form['appbundle_space[description]'] = 'Description de l\'espace créé lors du test automatisé.';
        $form['appbundle_space[activityDescription]'] = 'Artisanat, bureaux, production de test.';

        $client->submit($form);

        // Devrait enregistrer l'espace et rediriger vers la page d'édition
        $this->assertTrue($client->getResponse()->isRedirect());
        
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $spaceRepo = $em->getRepository(Space::class);
        $space = $spaceRepo->findOneBy(['name' => $spaceName]);

        $this->assertNotNull($space, 'L\'espace créé devrait être présent en base de données');
        $this->assertFalse($space->isClosed());

        // Publier/Activer l'espace pour que le candidat puisse soumettre sa candidature
        $space->setEnabled(true);
        $em->persist($space);
        $em->flush();

        // -------------------------------------------------------------
        // PARCOURS 2 suite : Candidature par le Porteur de Projet
        // -------------------------------------------------------------
        $client->loginUser($porteur);

        // Accéder à la page de candidature pour le nouvel espace
        $crawler = $client->request('GET', '/espaces/fiche/' . $space->getId() . '/apply');
        $this->assertSame(200, $client->getResponse()->getStatusCode(), 'La page candidature devrait répondre 200');

        $form = $crawler->filter('form')->form();
        $form['appbundle_application[name]'] = 'Projet de Test Journey';
        $form['appbundle_application[companyStatus]'] = 'Entreprise';
        $form['appbundle_application[wishedSize]'] = '50';
        $form['appbundle_application[description]'] = 'Présentation du projet de test pour la candidature.';
        $form['appbundle_application[localUsageDescription]'] = 'Utilisation en tant qu\'atelier de test.';
        $form['appbundle_application[category]'] = (string) $category->getId();

        $request = $client->getRequest();
        $session = $request->getSession();
        $requestStack = $client->getContainer()->get(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStack->push($request);

        $csrfTokenManager = $client->getContainer()->get(CsrfTokenManagerInterface::class);
        $csrfToken = $csrfTokenManager->getToken('appbundle_application')->getValue();

        $session->save();
        $requestStack->pop();

        $values = $form->getPhpValues();
        $values['appbundle_application']['_token'] = $csrfToken;
        $values['appbundle_application']['intent'] = 'submit';

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        if (!$client->getResponse()->isRedirect()) {
            $diagCrawler = new \Symfony\Component\DomCrawler\Crawler($client->getResponse()->getContent());
            $errors = [];
            foreach ($diagCrawler->filter('.help-block, .alert-danger, .invalid-feedback, .error-message') as $el) {
                $errors[] = trim($el->textContent);
            }
            fwrite(STDERR, "FORM ERRORS:\n" . implode("\n", $errors) . "\n");
        }

        // Devrait enregistrer la candidature et rediriger
        $this->assertTrue($client->getResponse()->isRedirect());

        $appRepo = $em->getRepository(Application::class);
        $application = $appRepo->findOneBy([
            'space' => $space->getId(),
            'name' => 'Projet de Test Journey'
        ]);

        $this->assertNotNull($application, 'La candidature devrait être présente en base de données');
        $this->assertSame(Application::UNREAD_STATUS, $application->getStatus());

        // -------------------------------------------------------------
        // PARCOURS 3 : Gestion de la candidature, Validation et Export
        // -------------------------------------------------------------
        $client->loginUser($owner);

        // 1. Export CSV des candidats de l'espace par l'owner
        $clientEm = $client->getContainer()->get(EntityManagerInterface::class);
        $apps = $clientEm->getRepository(Application::class)->findBy(['space' => $space->getId()]);
        fwrite(STDERR, "APPS COUNT IN DB FOR SPACE " . $space->getId() . ": " . count($apps) . "\n");
        foreach ($apps as $a) {
            fwrite(STDERR, "  - App ID: " . $a->getId() . ", Status: " . $a->getStatus() . ", Space ID: " . $a->getSpace()->getId() . "\n");
        }

        $client->request('GET', "/espace-manager/candidats/{$space->getId()}/export");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        
        $responseHeaders = $client->getResponse()->headers;
        $this->assertStringContainsString('application/csv', (string) $responseHeaders->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $responseHeaders->get('Content-Disposition'));
        
        $response = $client->getResponse();
        $csvContent = '';
        if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            $ref = new \ReflectionClass($response);
            $prop = $ref->getProperty('streamed');
            $prop->setAccessible(true);
            $prop->setValue($response, false);

            ob_start();
            $response->sendContent();
            $csvContent = (string) ob_get_clean();
        }
        $this->assertStringContainsString('Projet de Test Journey', $csvContent);

        // 2. Décision (Acceptation) de la candidature par l'owner
        $request = $client->getRequest();
        $session = $request->getSession();
        $requestStack = $client->getContainer()->get(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStack->push($request);

        $csrfTokenManager = $client->getContainer()->get(CsrfTokenManagerInterface::class);
        $csrfToken = $csrfTokenManager->getToken('candidates_action')->getValue();

        $session->save();
        $requestStack->pop();

        $client->request('POST', "/espace-manager/candidats/{$space->getId()}", [
            '_token' => $csrfToken,
            'applications' => (string) $application->getId(),
            'action' => 'accept',
            'message' => 'Félicitations, votre projet a été retenu !'
        ]);

        $this->assertTrue($client->getResponse()->isRedirect());

        // Recharger l'entité Candidature pour vérifier son statut
        $clientEm = $client->getContainer()->get(EntityManagerInterface::class);
        $application = $clientEm->getRepository(Application::class)->find($application->getId());
        $this->assertSame(Application::ACCEPT_STATUS, $application->getStatus(), 'La candidature devrait être acceptée');
    }

    private function ensureSpaceTypeExists(): SpaceType
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $type = $em->getRepository(SpaceType::class)->findOneBy([]);
        if ($type instanceof SpaceType) {
            return $type;
        }

        $type = new SpaceType();
        $type->setName('Bureaux / Atelier');
        $em->persist($type);
        $em->flush();

        return $type;
    }

    private function ensureCategoryExists(): Category
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $category = $em->getRepository(Category::class)->findOneBy(['isActive' => true]);
        if ($category instanceof Category) {
            return $category;
        }

        $category = new Category();
        $category->setName('Artisanat / Production de test');
        $category->setIsActive(true);
        $em->persist($category);
        $em->flush();

        return $category;
    }
}
