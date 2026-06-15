<?php

namespace App\Tests\Form;

use App\Entity\Parcel;
use App\Form\ParcelType;

class ParcelTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(ParcelType::class, new Parcel());
        $form->createView();

        $this->assertTrue($form->has('floor'));
    }
}
