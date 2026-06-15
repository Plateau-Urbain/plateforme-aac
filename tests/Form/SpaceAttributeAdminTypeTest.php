<?php

namespace App\Tests\Form;

use App\Entity\SpaceAttribute;
use App\Form\SpaceAttributeAdminType;

class SpaceAttributeAdminTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SpaceAttributeAdminType::class, new SpaceAttribute());
        $form->createView();

        $this->assertTrue($form->has('attribute'));
        $this->assertTrue($form->has('availability'));
    }
}
