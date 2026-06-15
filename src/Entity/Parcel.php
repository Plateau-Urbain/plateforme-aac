<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Parcel.
 */
#[ORM\Entity]
#[ORM\Table]
class Parcel implements \Stringable
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'min_surface', type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, minMessage: 'Vous devez obligatoirement renseigner une surface positive.', groups: ['draft', 'save'])]
    private $minSurface;

    /**
     * @var int
     */
    #[ORM\Column(name: 'max_surface', type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, minMessage: 'Vous devez obligatoirement renseigner une surface positive.', groups: ['draft', 'save'])]
    private $maxSurface;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'disponibility', type: 'date', nullable: true)]
    private $disponibility;

    #[ORM\ManyToOne(targetEntity: \App\Entity\LocalType::class)]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id')]
    #[Assert\NotBlank]
    private $type;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Floor::class)]
    #[ORM\JoinColumn(name: 'floor_id', referencedColumnName: 'id')]
    #[Assert\NotBlank]
    private $floor;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Space::class, inversedBy: 'parcels')]
    #[ORM\JoinColumn(name: 'space_id', referencedColumnName: 'id')]
    private $space;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set minSurface.
     *
     * @param int $minSurface
     *
     * @return Parcel
     */
    public function setMinSurface($minSurface)
    {
        $this->minSurface = $minSurface;

        return $this;
    }

    /**
     * Get minSurface.
     *
     * @return int
     */
    public function getMinSurface()
    {
        return $this->minSurface;
    }

    /**
     * Set maxSurface.
     *
     * @param int $maxSurface
     *
     * @return Parcel
     */
    public function setMaxSurface($maxSurface)
    {
        $this->maxSurface = $maxSurface;

        return $this;
    }

    /**
     * Get maxSurface.
     *
     * @return int
     */
    public function getMaxSurface()
    {
        return $this->maxSurface;
    }

    /**
     * Surface affichable (plage min–max ou valeur unique).
     */
    public function getSurface(): string
    {
        if ($this->minSurface === $this->maxSurface) {
            return (string) $this->minSurface;
        }

        return $this->minSurface . ' - ' . $this->maxSurface;
    }

    /**
     * @param Floor $floor
     *
     * @return Parcel
     */
    public function setFloor($floor)
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * Get surface.
     *
     * @return Floor
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /**
     * @param \DateTime $disponibility
     *
     * @return Parcel
     */
    public function setDisponibility($disponibility)
    {
        $this->disponibility = $disponibility;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDisponibility()
    {
        return $this->disponibility;
    }
   
    /**
     * @param Type
     *
     * @return Parcel
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Date
     */
    public function getType()
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->getMaxSurface().' m²';
    }

    /**
     * @return \App\Entity\Space|null
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

    /**
     * @return string
     */
    public function getDisponibilityToString()
    {
        if ($this->disponibility instanceof \DateTime) {
            return $this->disponibility->format('d/m/Y');
        }

        return 'Immédiate';
    }
}
