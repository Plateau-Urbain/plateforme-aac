<?php

namespace App\Tests\Form;

use App\Entity\SpaceVisit;
use App\Form\VisitType;

class VisitTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(VisitType::class, new SpaceVisit());
        $form->createView();

        $this->assertTrue($form->has('visitDate'));
        $this->assertTrue($form->has('startTime'));
        $this->assertTrue($form->has('endTime'));
    }
}
