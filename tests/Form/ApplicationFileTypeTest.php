<?php

namespace App\Tests\Form;

use App\Entity\ApplicationFile;
use App\Form\ApplicationFileType;

class ApplicationFileTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(ApplicationFileType::class, new ApplicationFile());
        $form->createView();

        $this->assertTrue($form->has('file'));
    }
}
