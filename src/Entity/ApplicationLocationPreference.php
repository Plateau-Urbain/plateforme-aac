<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'application_location_preference')]
#[ORM\UniqueConstraint(name: 'uniq_application_location', columns: ['application_id', 'location_id'])]
class ApplicationLocationPreference
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'locationPreferences')]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Application $application = null;

    #[ORM\ManyToOne(targetEntity: SpaceLocation::class)]
    #[ORM\JoinColumn(name: 'location_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(groups: ['submit'])]
    private ?SpaceLocation $location = null;

    #[ORM\Column(name: 'rank', type: 'integer')]
    #[Assert\NotNull(groups: ['submit'])]
    #[Assert\Positive(groups: ['submit'])]
    private ?int $rank = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): self
    {
        $this->application = $application;

        return $this;
    }

    public function getLocation(): ?SpaceLocation
    {
        return $this->location;
    }

    public function setLocation(?SpaceLocation $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(?int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }
}
