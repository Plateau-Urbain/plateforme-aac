<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * SpaceImage
 *
 */
#[ORM\Entity(repositoryClass: \App\Repository\SpaceImageRepository::class)]
#[ORM\Table(name: 'space_image')]
#[Vich\Uploadable]
class SpaceImage
{
    const FILETYPE_DOCUMENT_PLAN = 'document_plan';
    const FILETYPE_DOCUMENT_AAC = 'document_aac';
    const FILETYPE_DOCUMENT_FAQ = 'document_faq';
    const FILETYPE_IMAGE = 'image';

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[Vich\UploadableField(mapping: 'file', fileNameProperty: 'fileName')]
    protected $file;

    #[ORM\Column(name: 'file_name', type: 'string', nullable: true)]
    protected $fileName;

    /**
     * @var int
     */
    #[ORM\Column(name: 'file_type', type: 'string')]
    protected $fileType;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Space::class, inversedBy: 'pics')]
    #[ORM\JoinColumn(name: 'space_id', referencedColumnName: 'id')]
    protected $space;

    /**
     * @var int
     */
    #[ORM\Column(name: 'position', type: 'smallint')]
    protected $position;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'updatedAt', type: 'datetime')]
    private $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;

        if ($file) {
            $this->updatedAt = new \DateTime('now');
        }
    }

    /**
     * @return mixed
     */
    public function getFileType()
    {
        return ($this->fileType) ?: self::FILETYPE_IMAGE;
    }

    /**
     * @param mixed $fileType
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param mixed $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return mixed
     */
    public function getSpace(): ?\App\Entity\Space
    {
        return $this->space;
    }

    /**
     * @param mixed $space
     */
    public function setSpace($space)
    {
        $this->space = $space;
    }

    public function getSize()
    {
        return $this->file->getFileInfo()->getSize();
    }

    public function setParent($parent)
    {
        $this->setSpace($parent);
    }

    public function getPreview()
    {
        return (preg_match('/image\/.*/i', (string) $this->file->getMimeType()));
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Validation conditionnelle selon le type de fichier
     */
    #[Assert\Callback]
    public function validateFile(ExecutionContextInterface $context)
    {
        if ($this->file === null) {
            return;
        }

        $fileType = $this->getFileType();

        // Validation pour les images (section 2 - Photos du lieu)
        if ($fileType === self::FILETYPE_IMAGE) {
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 600 * 1024; // 600 Ko en octets
            $errorMessage = 'Seuls les formats JPEG, PNG et WebP sont acceptés pour les photos (max 600 Ko)';
        }
        // Validation pour les documents (section 4 - Documents ressources)
        else if ($fileType === self::FILETYPE_DOCUMENT_AAC || $fileType === self::FILETYPE_DOCUMENT_PLAN || $fileType === self::FILETYPE_DOCUMENT_FAQ) {
            $allowedMimeTypes = [
                'application/pdf',
                'application/x-pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            $maxSize = 10 * 1024 * 1024; // 10 Mo en octets
            $errorMessage = 'Seuls les formats PDF, DOC et DOCX sont acceptés pour les documents (max 10 Mo)';
        }
        else {
            // Par défaut, accepter les images
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 600 * 1024;
            $errorMessage = 'Format de fichier non reconnu';
        }

        // Vérification du type MIME
        if (!in_array($this->file->getMimeType(), $allowedMimeTypes)) {
            $context->buildViolation($errorMessage)
                ->atPath('file')
                ->addViolation();
        }

        // Vérification de la taille
        if ($this->file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 1);
            $context->buildViolation('Le fichier est trop volumineux (max ' . $maxSizeMB . ' Mo)')
                ->atPath('file')
                ->addViolation();
        }
    }
}
