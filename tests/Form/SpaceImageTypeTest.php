<?php

namespace App\Tests\Form;

use App\Entity\SpaceImage;
use App\Form\SpaceImageType;

class SpaceImageTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SpaceImageType::class, new SpaceImage());
        $form->createView();

        $this->assertTrue($form->has('file'));
    }
}
