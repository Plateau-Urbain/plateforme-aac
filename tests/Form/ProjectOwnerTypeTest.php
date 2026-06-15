<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Entity\UserDocument;
use App\Form\ProjectOwnerType;

/**
 * Régression migration : ProjectOwnerType ne doit pas appeler getData() null dans buildForm.
 */
class ProjectOwnerTypeTest extends AbstractFormTestCase
{


    public function testFormBuildsWithNullDataWithoutException(): void
    {
        $form = $this->formFactory->create(ProjectOwnerType::class, null, [
            'noPlainPassword' => true,
        ]);
        $form->createView();

        $this->assertTrue($form->has('userInfo'));
        $this->assertFalse($form->has('kbis'));
        $this->assertFalse($form->has('idcard'));
    }

    public function testFormAddsDocumentFieldsWhenUserHasNoDocuments(): void
    {
        $user = new User();
        $form = $this->formFactory->create(ProjectOwnerType::class, $user, [
            'noPlainPassword' => true,
        ]);

        $this->assertTrue($form->has('kbis'));
        $this->assertTrue($form->has('idcard'));
    }

    public function testFormSkipsDocumentFieldsWhenUserAlreadyHasDocuments(): void
    {
        $user = new User();
        $kbis = new UserDocument();
        $kbis->setType(UserDocument::KBIS_TYPE);
        $user->addDocument($kbis);

        $form = $this->formFactory->create(ProjectOwnerType::class, $user, [
            'noPlainPassword' => true,
        ]);

        $this->assertFalse($form->has('kbis'));
    }
}
