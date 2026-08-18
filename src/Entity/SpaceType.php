<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpaceType.
 */
#[ORM\Entity]
#[ORM\Table]
class SpaceType implements \Stringable
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
     * @var bool
     */
    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private $isActive = true;

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
     * @return bool
     */
    public function getIsActive()
    {
        return (bool) $this->isActive;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getIsActive();
    }

    /**
     * @param bool $isActive
     *
     * @return SpaceType
     */
    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
