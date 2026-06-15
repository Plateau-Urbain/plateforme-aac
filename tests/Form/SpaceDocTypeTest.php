<?php

namespace App\Tests\Form;

use App\Entity\SpaceImage;
use App\Form\SpaceDocType;

class SpaceDocTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SpaceDocType::class, new SpaceImage());
        $form->createView();

        $this->assertTrue($form->has('file'));
    }
}
