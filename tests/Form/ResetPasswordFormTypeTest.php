<?php

namespace App\Tests\Form;

use App\Form\ResetPasswordFormType;

class ResetPasswordFormTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(ResetPasswordFormType::class);
        $form->createView();

        $this->assertTrue($form->has('plainPassword'));
    }
}
