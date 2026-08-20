<?php

namespace App\Tests\Integration;

use App\Entity\Space;
use App\Entity\SpaceImage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SpacePriceValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testMissingPriceIsRejectedOnPublish(): void
    {
        $space = new Space();
        $paths = $this->violationPaths($space, ['save']);

        $this->assertContains('price', $paths);
    }

    public function testZeroPriceIsAcceptedOnPublish(): void
    {
        $space = new Space();
        $space->setPrice(0.0);
        $paths = $this->violationPaths($space, ['save']);

        $this->assertNotContains('price', $paths);
    }

    public function testCustomPriceTextIsEnoughOnPublish(): void
    {
        $space = new Space();
        $space->setPriceText('Sur devis');
        $paths = $this->violationPaths($space, ['save']);

        $this->assertNotContains('price', $paths);
    }

    public function testAccentedPhotoFilenameIsAccepted(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'space_photo_');
        copy(__DIR__ . '/../../public/images/groupe_ajouter_espace.png', $tmp);
        $uploaded = new UploadedFile($tmp, 'façade paris.png', 'image/png', null, true);

        $image = new SpaceImage();
        $image->setFile($uploaded);
        $image->setFileType(SpaceImage::FILETYPE_IMAGE);
        $image->setPosition(0);

        $violations = $this->validator->validate($image);
        $filenameErrors = [];
        foreach ($violations as $violation) {
            if (str_contains($violation->getMessage(), 'nom non valide')) {
                $filenameErrors[] = $violation->getMessage();
            }
        }

        $this->assertSame(
            [],
            $filenameErrors,
            'Un nom de photo avec accents/espaces ne doit plus bloquer la publication.'
        );
    }

    /**
     * @param list<string> $groups
     * @return list<string>
     */
    private function violationPaths(Space $space, array $groups): array
    {
        $paths = [];
        foreach ($this->validator->validate($space, null, $groups) as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        return $paths;
    }
}
