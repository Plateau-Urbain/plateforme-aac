<?php

namespace App\Tests\Form;

use App\Entity\UserDocument;
use App\Form\UserDocumentType;

class UserDocumentTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(UserDocumentType::class, new UserDocument());
        $form->createView();

        $this->assertTrue($form->has('file'));
    }
}
