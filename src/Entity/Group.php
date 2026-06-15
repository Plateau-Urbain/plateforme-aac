<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Group.
 */
#[ORM\Entity]
#[ORM\Table(name: 'fos_group')]
class Group implements \Stringable
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    protected $name;

    #[ORM\Column(type: 'json')]
    protected $roles = [];

    #[ORM\ManyToMany(targetEntity: \App\Entity\User::class, mappedBy: 'groups')]
    protected $users;

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUsers()
    {
        return $this->users;
    }
}
