<?php

namespace App\Tests\Form;

use App\Entity\Space;
use App\Entity\SpaceImage;
use App\Form\SpaceType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class SpaceTypeTest extends AbstractFormTestCase
{
    public function testFormBuildsAndCompiles(): void
    {
        $space = new Space();
        $form = $this->formFactory->create(SpaceType::class, $space);
        $form->createView();

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('doc_aac'));
        $this->assertTrue($form->has('doc_plan'));
        $this->assertTrue($form->has('doc_faq'));
    }

    public function testDocAacFieldAbsentWhenSpaceAlreadyHasAacDoc(): void
    {
        // Si l'espace a déjà un doc AAC en base, le champ d'upload doit être absent
        $space = new Space();
        $space->addDoc(new SpaceImage(), SpaceImage::FILETYPE_DOCUMENT_AAC);

        $form = $this->formFactory->create(SpaceType::class, $space);

        $this->assertFalse($form->has('doc_aac'));
        // Les autres champs de doc sont toujours présents
        $this->assertTrue($form->has('doc_plan'));
        $this->assertTrue($form->has('doc_faq'));
    }

    public function testAllDocFieldsAbsentWhenSpaceHasAllDocs(): void
    {
        // Si tous les docs existent déjà, aucun champ d'upload doc ne doit apparaître
        $space = new Space();
        $space->addDoc(new SpaceImage(), SpaceImage::FILETYPE_DOCUMENT_AAC);
        $space->addDoc(new SpaceImage(), SpaceImage::FILETYPE_DOCUMENT_PLAN);
        $space->addDoc(new SpaceImage(), SpaceImage::FILETYPE_DOCUMENT_FAQ);

        $form = $this->formFactory->create(SpaceType::class, $space);

        $this->assertFalse($form->has('doc_aac'));
        $this->assertFalse($form->has('doc_plan'));
        $this->assertFalse($form->has('doc_faq'));
    }

    public function testAddPhotoSubmissionBindsUploadedFileToPicsField(): void
    {
        $space = new Space();
        $form = $this->formFactory->create(SpaceType::class, $space, [
            'csrf_protection' => false,
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'space_photo_');
        copy(__DIR__ . '/../../public/images/groupe_ajouter_espace.png', $tmp);
        $uploaded = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

        $request = Request::create('/', 'POST', [
            'appbundle_space' => [
                'name' => 'Espace test',
            ],
            'add_photo' => '1',
        ], [], [
            'appbundle_space' => [
                'pics' => [
                    'file' => [
                        'file' => $uploaded,
                    ],
                ],
            ],
        ]);

        $form->handleRequest($request);

        $this->assertTrue($form->isSubmitted());
        $newImage = $form->get('pics')->getData();
        $this->assertInstanceOf(SpaceImage::class, $newImage);
        $this->assertNotNull($newImage->getFile());
    }
}
