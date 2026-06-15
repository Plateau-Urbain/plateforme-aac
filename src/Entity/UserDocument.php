<?php

namespace App\Entity;

use Avocode\FormExtensionsBundle\Form\Model\UploadCollectionFileInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UserDocument
 *
 */
#[ORM\Entity]
#[ORM\Table]
#[Vich\Uploadable]
class UserDocument implements \Stringable
{
    const NO_TYPE  = '';
    const ID_TYPE  = 'id';
    const KBIS_TYPE   = 'kbis';

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'type', type: 'string')]
    private $type = self::NO_TYPE;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(name: 'updatedAt', type: 'datetime')]
    private $updatedAt;

    #[Vich\UploadableField(mapping: 'user_documents', fileNameProperty: 'fileName')]
    #[Assert\File(maxSize: '10M', mimeTypes: ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf', 'application/x-pdf', 'application/msword'])]
    private $file;

    /**
     * @var string
     */
    #[ORM\Column(name: 'file_name', type: 'string', nullable: true)]
    private $fileName;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'projectHolder_id', referencedColumnName: 'id', nullable: true)]
    protected $projectHolder;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return UserDocument
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $file
     */
    public function setFile(\Symfony\Component\HttpFoundation\File\File $file = null)
    {
        $this->file = $file;

        if ($file) {
            $this->updatedAt = new \DateTime('now');
        }

        return $this;
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     * @return UserDocument
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set projectHolder
     *
     * @param \App\Entity\User $projectHolder
     * @return UserDocument
     */
    public function setUser(\App\Entity\User $projectHolder = null)
    {
        $this->projectHolder = $projectHolder;

        return $this;
    }

    /**
     * Get projectHolder
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->projectHolder;
    }

    /**
     * Set projectHolder
     *
     * @param \App\Entity\User $projectHolder
     * @return UserDocument
     */
    public function setProjectHolder(\App\Entity\User $projectHolder = null)
    {
        $this->projectHolder = $projectHolder;

        return $this;
    }

    /**
     * Get projectHolder
     *
     * @return \App\Entity\User
     */
    public function getProjectHolder()
    {
        return $this->projectHolder;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return UserDocument
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function getOriginalname()
    {
        return preg_replace('/^[^_]*_/', '', (string) $this->fileName);
    }

    public function __toString(): string
    {
        if ($this->fileName !== null && $this->fileName !== '') {
            $displayName = $this->getOriginalname();

            return $displayName !== '' ? $displayName : $this->fileName;
        }

        return match ($this->type) {
            self::ID_TYPE => 'Pièce d\'identité',
            self::KBIS_TYPE => 'Extrait KBIS',
            default => 'Document utilisateur',
        };
    }
}
