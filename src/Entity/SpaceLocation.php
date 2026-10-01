<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'space_location')]
#[Vich\Uploadable]
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
    #[Assert\NotBlank(message: 'Veuillez renseigner la description du site.', groups: ['save', 'draft'])]
    private ?string $description = null;

    #[ORM\Column(name: 'activity_description', type: 'text', nullable: true)]
    private ?string $activityDescription = null;

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
    #[Assert\NotBlank(message: 'Veuillez renseigner la durée du projet.', groups: ['save', 'draft'])]
    #[Assert\Length(max: 255, groups: ['save', 'draft'])]
    private ?string $availability = null;

    #[Vich\UploadableField(mapping: 'file', fileNameProperty: 'aacDocumentName')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: [
            'application/pdf',
            'application/x-pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        mimeTypesMessage: 'Seuls les formats PDF, DOC et DOCX sont acceptés pour le document d\'appel à candidature.',
        maxSizeMessage: 'Le document d\'appel à candidature est trop volumineux ({{ size }} {{ suffix }}). La taille maximale est de {{ limit }} {{ suffix }}.',
        groups: ['Default', 'save', 'draft'],
    )]
    private ?File $aacDocument = null;

    #[ORM\Column(name: 'aac_document_name', type: 'string', length: 255, nullable: true)]
    private ?string $aacDocumentName = null;

    #[Vich\UploadableField(mapping: 'file', fileNameProperty: 'planDocumentName')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: [
            'application/pdf',
            'application/x-pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        mimeTypesMessage: 'Seuls les formats PDF, DOC et DOCX sont acceptés pour le document de répartition des espaces.',
        maxSizeMessage: 'Le document de répartition des espaces est trop volumineux ({{ size }} {{ suffix }}). La taille maximale est de {{ limit }} {{ suffix }}.',
        groups: ['Default', 'save', 'draft'],
    )]
    private ?File $planDocument = null;

    #[ORM\Column(name: 'plan_document_name', type: 'string', length: 255, nullable: true)]
    private ?string $planDocumentName = null;

    #[Vich\UploadableField(mapping: 'file', fileNameProperty: 'faqDocumentName')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: [
            'application/pdf',
            'application/x-pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        mimeTypesMessage: 'Seuls les formats PDF, DOC et DOCX sont acceptés pour la F.A.Q.',
        maxSizeMessage: 'La F.A.Q est trop volumineuse ({{ size }} {{ suffix }}). La taille maximale est de {{ limit }} {{ suffix }}.',
        groups: ['Default', 'save', 'draft'],
    )]
    private ?File $faqDocument = null;

    #[ORM\Column(name: 'faq_document_name', type: 'string', length: 255, nullable: true)]
    private ?string $faqDocumentName = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

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

    public function getActivityDescription(): ?string
    {
        return $this->activityDescription;
    }

    public function setActivityDescription(?string $activityDescription): self
    {
        $this->activityDescription = $activityDescription;

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

    public function getAacDocument(): ?File
    {
        return $this->aacDocument;
    }

    public function setAacDocument(?File $aacDocument = null): self
    {
        $this->aacDocument = $aacDocument;

        if ($aacDocument !== null) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    public function getAacDocumentName(): ?string
    {
        return $this->aacDocumentName;
    }

    /**
     * Le namer Vich (OrignameNamer) préfixe le nom d'origine par un uniqid de 13 caractères.
     */
    private static function documentDisplayName(?string $storedName): ?string
    {
        if ($storedName === null) {
            return null;
        }

        return preg_replace('/^[0-9a-f]{13}_/', '', $storedName);
    }

    public function getAacDocumentDisplayName(): ?string
    {
        return self::documentDisplayName($this->aacDocumentName);
    }

    public function setAacDocumentName(?string $aacDocumentName): self
    {
        $this->aacDocumentName = $aacDocumentName;

        return $this;
    }

    public function getPlanDocument(): ?File
    {
        return $this->planDocument;
    }

    public function setPlanDocument(?File $planDocument = null): self
    {
        $this->planDocument = $planDocument;

        if ($planDocument !== null) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    public function getPlanDocumentName(): ?string
    {
        return $this->planDocumentName;
    }

    public function getPlanDocumentDisplayName(): ?string
    {
        return self::documentDisplayName($this->planDocumentName);
    }

    public function setPlanDocumentName(?string $planDocumentName): self
    {
        $this->planDocumentName = $planDocumentName;

        return $this;
    }

    public function getFaqDocument(): ?File
    {
        return $this->faqDocument;
    }

    public function setFaqDocument(?File $faqDocument = null): self
    {
        $this->faqDocument = $faqDocument;

        if ($faqDocument !== null) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    public function getFaqDocumentName(): ?string
    {
        return $this->faqDocumentName;
    }

    public function getFaqDocumentDisplayName(): ?string
    {
        return self::documentDisplayName($this->faqDocumentName);
    }

    public function setFaqDocumentName(?string $faqDocumentName): self
    {
        $this->faqDocumentName = $faqDocumentName;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
