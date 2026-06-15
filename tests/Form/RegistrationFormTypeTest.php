<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\RegistrationFormType;

class RegistrationFormTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(RegistrationFormType::class, new User());
        $form->createView();

        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('plainPassword'));
        $this->assertTrue($form->has('captcha'));
    }
}
