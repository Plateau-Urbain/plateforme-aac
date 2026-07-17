<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\SpaceOwnerType;

class SpaceOwnerTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SpaceOwnerType::class, new User());
        $form->createView();

        $this->assertTrue($form->has('userInfo'));
        $this->assertTrue($form->has('companyInfo'));
    }

    public function testProfileFieldsNotExposedInOwnerForm(): void
    {
        // Migration SF6 : SpaceOwnerType supprime les champs non pertinents pour un propriétaire
        $form = $this->formFactory->create(SpaceOwnerType::class, new User());

        // username supprimé au niveau racine
        $this->assertFalse($form->has('username'));
        // phone, birthday, description supprimés de userInfo
        $this->assertFalse($form->get('userInfo')->has('phone'));
        $this->assertFalse($form->get('userInfo')->has('birthday'));
        $this->assertFalse($form->get('userInfo')->has('description'));
    }

    public function testCompanyInfoSectionDoesNotExposeUnneededFields(): void
    {
        // Les champs non pertinents pour un proprio sont supprimés de companyInfo
        $form = $this->formFactory->create(SpaceOwnerType::class, new User());

        $companyInfo = $form->get('companyInfo');
        $this->assertFalse($companyInfo->has('companyDescription'));
        $this->assertFalse($companyInfo->has('companyEffective'));
        $this->assertFalse($companyInfo->has('companyStructures'));
        $this->assertFalse($companyInfo->has('companyBlog'));
        $this->assertFalse($companyInfo->has('isPuShareholder'));
        $this->assertFalse($companyInfo->has('isSubjectToVat'));
    }
}
