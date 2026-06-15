<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\CompanyType;

class CompanyTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $user = new User();

        // Since CompanyType has inherit_data => true, we must add it to a parent form
        $parentBuilder = $this->formFactory->createBuilder(\Symfony\Component\Form\Extension\Core\Type\FormType::class, $user);
        $parentBuilder->add('companyInfo', CompanyType::class);
        $form = $parentBuilder->getForm();
        $form->createView();

        $this->assertTrue($form->has('companyInfo'));
        $this->assertTrue($form->get('companyInfo')->has('company'));
    }

    public function testLegacyCompanyStatusBuildsAndRendersWithoutCrash(): void
    {
        // Régression migration : un statut hors catalogue (ancienne valeur en base)
        // ne doit pas lever d'exception à la construction ni au rendu.
        $user = new User();
        $user->setCompanyStatus('EIRL');  // EIRL n'est plus dans le catalogue actuel

        $parentBuilder = $this->formFactory->createBuilder(\Symfony\Component\Form\Extension\Core\Type\FormType::class, $user);
        $parentBuilder->add('companyInfo', CompanyType::class, [
            'legacy_company_status' => $user->getCompanyStatus(),
        ]);
        $form = $parentBuilder->getForm();
        $view = $form->createView();

        // Le formulaire s'est rendu et companyStatus est bien présent
        $this->assertArrayHasKey('companyStatus', $view['companyInfo']->children);

        // Le statut hérité doit figurer parmi les choix dans la vue
        $statusChoiceValues = array_map(
            fn($c) => $c->value,
            $view['companyInfo']['companyStatus']->vars['choices']
        );
        $this->assertContains('EIRL', $statusChoiceValues, 'Le statut hérité EIRL doit être dans les choix.');
        $this->assertContains('SAS', $statusChoiceValues, 'Les statuts du catalogue courant doivent rester.');
    }

    public function testKnownCompanyStatusHasNoDuplicateEntry(): void
    {
        // Un statut dans le catalogue courant ne génère pas d'entrée de warning en double
        $user = new User();
        $user->setCompanyStatus('SAS');

        $parentBuilder = $this->formFactory->createBuilder(\Symfony\Component\Form\Extension\Core\Type\FormType::class, $user);
        $parentBuilder->add('companyInfo', CompanyType::class, [
            'legacy_company_status' => $user->getCompanyStatus(),
        ]);
        $form = $parentBuilder->getForm();
        $view = $form->createView();

        $statusChoiceValues = array_map(
            fn($c) => $c->value,
            $view['companyInfo']['companyStatus']->vars['choices']
        );

        // SAS ne doit apparaître qu'une fois
        $this->assertSame(1, array_count_values($statusChoiceValues)['SAS'] ?? 0);
    }
}
