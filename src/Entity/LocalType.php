<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocalType.
 */
#[ORM\Entity]
#[ORM\Table]
class LocalType implements \Stringable
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

    public function __toString(): string
    {
        return $this->getName();
    }
}
