<?php

namespace App\Tests\Form;

use App\Entity\Application;
use App\Entity\Space;
use App\Entity\User;
use App\Form\ApplicationType;

class ApplicationTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $user = new User();
        $space = new Space();
        $application = new Application();
        $application->setSpace($space);

        $form = $this->formFactory->create(ApplicationType::class, $application, [
            'user' => $user,
        ]);
        $form->createView();

        $this->assertTrue($form->has('name'));
    }

    public function testAnonymousUserKeepsPasswordFieldInProjectHolder(): void
    {
        // Un utilisateur sans ID (anonyme) conserve le champ plainPassword
        $user = new User();
        $application = new Application();
        $application->setSpace(new Space());

        $form = $this->formFactory->create(ApplicationType::class, $application, [
            'user' => $user,
        ]);

        $projectHolder = $form->get('projectHolder');
        $this->assertTrue($projectHolder->get('userInfo')->has('plainPassword'));
    }

    public function testFreezeProfileSectionsDisablesUserInfoAndCompanyInfo(): void
    {
        // L'option freeze_profile_sections désactive les sections profil en lecture seule
        $user = new User();
        $application = new Application();
        $application->setSpace(new Space());

        $form = $this->formFactory->create(ApplicationType::class, $application, [
            'user' => $user,
            'freeze_profile_sections' => true,
        ]);

        $projectHolder = $form->get('projectHolder');
        $this->assertTrue($projectHolder->get('userInfo')->isDisabled());
        $this->assertTrue($projectHolder->get('companyInfo')->isDisabled());
    }

    public function testFormWithFreezeStillHasProjectHolderSection(): void
    {
        // Même en mode freeze, le sous-formulaire projectHolder reste présent
        $user = new User();
        $application = new Application();
        $application->setSpace(new Space());

        $form = $this->formFactory->create(ApplicationType::class, $application, [
            'user' => $user,
            'freeze_profile_sections' => true,
        ]);
        $form->createView();

        $this->assertTrue($form->has('projectHolder'));
        $this->assertTrue($form->has('name'));
    }

    public function testAuthenticatedUserHasNoPasswordFieldInProjectHolder(): void
    {
        // Régression migration : un utilisateur connecté (ID non null) ne doit pas
        // voir plainPassword dans le formulaire de candidature (sécurité).
        $user = new User();
        $user->setEmail('test-apply@plateau-urbain.test');
        $user->setEmailCanonical('test-apply@plateau-urbain.test');
        $user->setEnabled(true);
        $user->setPassword('hashed');
        $user->setCreatedAt(new \DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $application = new Application();
        $application->setSpace(new Space());

        $form = $this->formFactory->create(ApplicationType::class, $application, [
            'user' => $user,
        ]);

        $this->assertFalse($form->get('projectHolder')->get('userInfo')->has('plainPassword'));
    }
}
