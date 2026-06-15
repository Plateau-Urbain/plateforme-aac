<?php

namespace App\Tests\Form;

use App\Entity\SpaceImage;
use App\Form\SpaceDocAdminType;

class SpaceDocAdminTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SpaceDocAdminType::class, new SpaceImage());
        $form->createView();

        $this->assertTrue($form->has('file'));
    }
}
