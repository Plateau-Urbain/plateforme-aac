<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpaceAttribute.
 */
#[ORM\Entity]
#[ORM\Table]
class Attribute implements \Stringable
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    protected $name;

    #[ORM\OneToMany(targetEntity: \App\Entity\SpaceAttribute::class, mappedBy: 'attribute', cascade: ['persist'])]
    private $tags;

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
     * Set name.
     *
     * @param string $name
     *
     * @return SpaceAttribute
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return SpaceAttribute
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
