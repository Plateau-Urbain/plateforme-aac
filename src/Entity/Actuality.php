<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Actuality
 *
 */
#[ORM\Entity(repositoryClass: \App\Repository\ActualityRepository::class)]
#[ORM\Table]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Actuality
{
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
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'subtitle', type: 'string', length: 255, nullable: true)]
    private $subtitle;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'date', type: 'datetime', nullable: true)]
    private $date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'link', type: 'string', length: 255, nullable: true)]
    private $link;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'updatedAt', type: 'datetime')]
    private $updatedAt;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'published', type: 'boolean', nullable: true)]
    private $published = false;

    #[Vich\UploadableField(mapping: 'actuality', fileNameProperty: 'imageName')]
    #[Assert\Image(maxSize: '20M')]
    protected $image;

    #[ORM\Column(name: 'image_name', type: 'string', nullable: true)]
    protected $imageName;

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
     * Set title
     *
     * @param string $title
     * @return Actuality
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set subtitle
     *
     * @param string $subtitle
     * @return Actuality
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Get subtitle
     *
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Actuality
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return Actuality
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set published
     *
     * @param boolean $published
     * @return Actuality
     */
    public function setPublished($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Get published
     *
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @return File
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return Actuality
     */
    public function setImage(File $image = null)
    {
        $this->image = $image;

        if ($image) {
            $this->updatedAt = new \DateTime('now');
        }

        return $this;
    }

    /**
     * Set imageName
     *
     * @param string $imageName
     * @return Actuality
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Actuality
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

    #[ORM\PrePersist]
    public function initializeUpdatedAtOnCreate(): void
    {
        if ($this->updatedAt === null) {
            $this->updatedAt = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
