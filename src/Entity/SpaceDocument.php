<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpaceDocument
 */
#[ORM\Entity]
#[ORM\Table]
class SpaceDocument
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
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Space::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'space_id', referencedColumnName: 'id')]
    protected $space;

    #[ORM\OneToMany(targetEntity: \App\Entity\ApplicationFile::class, mappedBy: 'spaceDocument', cascade: ['persist', 'remove'])]
    private $files;

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
     * Set name
     *
     * @param string $name
     * @return SpaceDocument
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set space
     *
     * @param \App\Entity\Space $space
     * @return SpaceDocument
     */
    public function setSpace(\App\Entity\Space $space = null)
    {
        $this->space = $space;

        return $this;
    }

    /**
     * Get space
     *
     * @return \App\Entity\Space
     */
    public function getSpace()
    {
        return $this->space;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->files = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add files
     *
     * @param \App\Entity\ApplicationFile $files
     * @return SpaceDocument
     */
    public function addFile(\App\Entity\ApplicationFile $files)
    {
        $this->files[] = $files;

        return $this;
    }

    /**
     * Remove files
     *
     * @param \App\Entity\ApplicationFile $files
     */
    public function removeFile(\App\Entity\ApplicationFile $files)
    {
        $this->files->removeElement($files);
    }

    /**
     * Get files
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFiles()
    {
        return $this->files;
    }
}
