<?php

namespace App\Tests\Form;

use App\Entity\SpaceAttribute;
use App\Form\SpaceAttributeType;

class SpaceAttributeTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SpaceAttributeType::class, new SpaceAttribute());
        $form->createView();

        $this->assertTrue($form->has('availability'));
    }
}
