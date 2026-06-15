<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UseType
 */
#[ORM\Entity]
#[ORM\Table(name: 'use_type')]
class UseType implements \Stringable
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

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private $isActive = true;


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
     * @return UseType
     */
    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Category
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

    public function __toString(): string
    {
        return strval($this->getName());
    }
}
