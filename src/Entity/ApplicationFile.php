<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Applicaiton file.
 *
 */
#[ORM\Entity]
#[ORM\Table(name: 'application_file')]
#[Vich\Uploadable]
class ApplicationFile
{
    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[Vich\UploadableField(mapping: 'application', fileNameProperty: 'fileName')]
    #[Assert\File(maxSize: '20M')]
    protected $file;

    #[ORM\Column(name: 'file_name', type: 'string', nullable: true)]
    protected $fileName;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Application::class, inversedBy: 'files')]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id')]
    protected $application;

    #[ORM\ManyToOne(targetEntity: \App\Entity\SpaceDocument::class, inversedBy: 'files')]
    #[ORM\JoinColumn(name: 'space_document_id', referencedColumnName: 'id')]
    protected $spaceDocument;

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
     * @param File $file
     *
     * @return $this;
     */
    public function setFile(File $file = null)
    {
        if ($file === null) {
            return;
        }

        $this->file = $file;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param Application $application
     */
    public function setApplication($application)
    {
        $this->application = $application;
    }

    /**
     * Set spaceDocument
     *
     * @param \App\Entity\SpaceDocument $spaceDocument
     * @return SpaceDocument
     */
    public function setSpaceDocument(\App\Entity\SpaceDocument $spaceDocument = null)
    {
        $this->spaceDocument = $spaceDocument;

        return $this;
    }

    /**
     * Get spaceDocument
     *
     * @return \App\Entity\Application
     */
    public function getSpaceDocument()
    {
        return $this->spaceDocument;
    }
}
