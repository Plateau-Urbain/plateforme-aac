<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table(name: 'space_location')]
class SpaceLocation
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Space::class, inversedBy: 'locations')]
    #[ORM\JoinColumn(name: 'space_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Space $space = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[Assert\NotBlank(groups: ['save', 'draft'])]
    #[Assert\Length(max: 255, groups: ['save', 'draft'])]
    private ?string $name = null;

    #[ORM\Column(name: 'address', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255, groups: ['save', 'draft'])]
    private ?string $address = null;

    #[ORM\Column(name: 'zip_code', type: 'string', length: 5, nullable: true)]
    #[Assert\NotBlank(groups: ['save'])]
    #[Assert\Length(min: 5, max: 5, groups: ['save'])]
    #[Assert\Regex(pattern: '/[0-9]{5}/', message: 'Code postal invalide', groups: ['save'])]
    private ?string $zipCode = null;

    #[ORM\Column(name: 'city', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['save', 'draft'])]
    #[Assert\Length(max: 255, groups: ['save', 'draft'])]
    private ?string $city = null;

    #[ORM\Column(name: 'latitude', type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(name: 'longitude', type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'is_erp', type: 'boolean', options: ['default' => false])]
    private bool $isErp = false;

    #[ORM\Column(name: 'display_order', type: 'integer', options: ['default' => 0])]
    private int $displayOrder = 0;

    #[ORM\Column(name: 'suspended', type: 'boolean', options: ['default' => false])]
    private bool $suspended = false;

    #[ORM\Column(name: 'suspension_message', type: 'text', nullable: true)]
    private ?string $suspensionMessage = null;

    #[ORM\Column(name: 'suspended_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $suspendedAt = null;

    #[ORM\Column(name: 'availability', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['save'])]
    #[Assert\Length(max: 255, groups: ['save', 'draft'])]
    private ?string $availability = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSpace(): ?Space
    {
        return $this->space;
    }

    public function setSpace(?Space $space): self
    {
        $this->space = $space;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isErp(): bool
    {
        return $this->isErp;
    }

    public function setIsErp(bool $isErp): self
    {
        $this->isErp = $isErp;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(?int $displayOrder): self
    {
        $this->displayOrder = $displayOrder ?? 0;

        return $this;
    }

    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    public function setSuspended(bool $suspended): self
    {
        if ($suspended && !$this->suspended) {
            $this->suspendedAt = new \DateTime();
        }
        if (!$suspended) {
            $this->suspendedAt = null;
            $this->suspensionMessage = null;
        }
        $this->suspended = $suspended;

        return $this;
    }

    public function getSuspensionMessage(): ?string
    {
        return $this->suspensionMessage;
    }

    public function setSuspensionMessage(?string $suspensionMessage): self
    {
        $this->suspensionMessage = $suspensionMessage;

        return $this;
    }

    public function getSuspendedAt(): ?\DateTimeInterface
    {
        return $this->suspendedAt;
    }

    public function setSuspendedAt(?\DateTimeInterface $suspendedAt): self
    {
        $this->suspendedAt = $suspendedAt;

        return $this;
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            trim((string) $this->address),
            trim(sprintf('%s %s', (string) $this->zipCode, (string) $this->city)),
        ]);

        return implode(', ', $parts);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    #[Assert\Callback(groups: ['save', 'draft'])]
    public function validateSuspension(ExecutionContextInterface $context): void
    {
        if ($this->suspended && trim((string) $this->suspensionMessage) === '') {
            $context->buildViolation('Un message est requis lorsque le lieu est suspendu.')
                ->atPath('suspensionMessage')
                ->addViolation();
        }
    }

    public function getAvailability(): ?string
    {
        return $this->availability;
    }

    public function setAvailability(?string $availability): self
    {
        $this->availability = $availability;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
