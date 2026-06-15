<?php

namespace App\Tests\Form;

use App\Entity\SpaceDocument;
use App\Form\SpaceDocumentType;

class SpaceDocumentTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SpaceDocumentType::class, new SpaceDocument());
        $form->createView();

        $this->assertTrue($form->has('name'));
    }
}
