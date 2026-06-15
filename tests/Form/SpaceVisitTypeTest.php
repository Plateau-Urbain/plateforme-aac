<?php

namespace App\Tests\Form;

use App\Entity\SpaceVisit;
use App\Form\SpaceVisitType;

class SpaceVisitTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SpaceVisitType::class, new SpaceVisit());
        $form->createView();

        $this->assertTrue($form->has('visitDate'));
        $this->assertTrue($form->has('startTime'));
        $this->assertTrue($form->has('endTime'));
    }
}
