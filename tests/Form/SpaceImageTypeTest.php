<?php

namespace App\Tests\Form;

use App\Entity\SpaceImage;
use App\Form\SpaceImageType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SpaceImageTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $form = $this->formFactory->create(SpaceImageType::class, new SpaceImage());
        $form->createView();

        $this->assertTrue($form->has('file'));
    }

    public function testFileSubmissionBindsUploadedFile(): void
    {
        $form = $this->formFactory->create(SpaceImageType::class, new SpaceImage(), [
            'csrf_protection' => false,
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'space_photo_');
        copy(__DIR__ . '/../../public/images/groupe_ajouter_espace.png', $tmp);
        $uploaded = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

        $form->submit([
            'file' => [
                'file' => $uploaded,
            ],
        ]);

        $data = $form->getData();
        $this->assertInstanceOf(SpaceImage::class, $data);
        $this->assertNotNull($data->getFile());
    }
}
