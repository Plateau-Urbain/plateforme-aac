<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Category
 */
#[ORM\Entity(repositoryClass: \App\Repository\CategoryRepository::class)]
#[ORM\Table]
class Category implements \Stringable
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
     * Quand true, ce "type d'usage" n'est proposé que pour les espaces ERP.
     *
     * @var bool
     */
    #[ORM\Column(name: 'requires_erp', type: 'boolean', options: ['default' => false])]
    private $requiresErp = false;


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
     * @return Category
     */
    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRequiresErp()
    {
        return (bool) $this->requiresErp;
    }

    /**
     * @return bool
     */
    public function requiresErp()
    {
        return $this->getRequiresErp();
    }

    /**
     * @param bool $requiresErp
     * @return Category
     */
    public function setRequiresErp($requiresErp)
    {
        $this->requiresErp = (bool) $requiresErp;

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
        return $this->getName();
    }
}
