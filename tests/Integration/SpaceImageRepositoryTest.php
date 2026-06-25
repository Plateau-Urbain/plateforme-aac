<?php

namespace App\Tests\Integration;

use App\Entity\Space;
use App\Entity\SpaceImage;
use App\Repository\SpaceImageRepository;

class SpaceImageRepositoryTest extends AbstractDoctrineTestCase
{
    public function testRepositoryCanLoadAndRemoveSpaceImage(): void
    {
        $repository = $this->entityManager->getRepository(SpaceImage::class);
        $this->assertInstanceOf(SpaceImageRepository::class, $repository);

        $space = new Space();
        $space->setName('Espace test images');
        $space->setDescription('Description test');
        $space->setActivityDescription('Activité test');

        $image = new SpaceImage();
        $image->setFileType(SpaceImage::FILETYPE_IMAGE);
        $image->setPosition(0);
        $image->setFileName('test-image.jpg');
        $image->setUpdatedAt(new \DateTime());
        $space->addPic($image);

        $this->entityManager->persist($space);
        $this->entityManager->flush();

        $imageId = $image->getId();
        $this->assertNotNull($imageId);

        $loaded = $repository->find($imageId);
        $this->assertInstanceOf(SpaceImage::class, $loaded);

        $this->entityManager->remove($loaded);
        $this->entityManager->flush();

        $this->assertNull($repository->find($imageId));
    }
}
