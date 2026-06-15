<?php

namespace App\Tests\Form;

use App\Form\SearchType;

class SearchTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SearchType::class);
        $form->createView();

        $this->assertTrue($form->has('localType'));
        $this->assertTrue($form->has('minimumPrice'));
    }
}
