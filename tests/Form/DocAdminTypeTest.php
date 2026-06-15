<?php

namespace App\Tests\Form;

use App\Form\DocAdminType;

class DocAdminTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(DocAdminType::class);
        $form->createView();

        $this->assertNotNull($form);
    }
}
