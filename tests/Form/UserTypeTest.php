<?php

namespace App\Tests\Form;

use App\Entity\User;
use App\Form\UserType;

class UserTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $user = new User();
        
        $parentBuilder = $this->formFactory->createBuilder(\Symfony\Component\Form\Extension\Core\Type\FormType::class, $user);
        $parentBuilder->add('userInfo', UserType::class);
        $form = $parentBuilder->getForm();
        $form->createView();

        $this->assertTrue($form->has('userInfo'));
        $this->assertTrue($form->get('userInfo')->has('civility'));
        $this->assertTrue($form->get('userInfo')->has('firstname'));
    }
}
