<?php
// vim:expandtab:sw=4 softtabstop=4:

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Entity\User;
use App\Entity\SpaceImage;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Space
 */
#[ORM\Entity(repositoryClass: \App\Repository\SpaceRepository::class)]
#[ORM\Table]
#[ORM\HasLifecycleCallbacks]
class Space implements \Stringable
{
    const MAX_PICTURES_UPLOAD = 20;

    public const WORKFLOW_STANDARD = 'standard';

    public const WORKFLOW_MULTI_LOCATION = 'multi_location';

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * Indique si le lieu est un Établissement Recevant du Public (ERP).
     * Certains "types d'usage" ne sont proposés que pour les ERP.
     *
     * @var bool
     */
    #[ORM\Column(name: 'is_erp', type: 'boolean', options: ['default' => false])]
    private $isErp = false;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['save'])]
    #[Assert\Length(max: 255)]
    private $name;

    #[ORM\Column(name: 'created', type: 'datetime')]
    private $created;

    #[ORM\Column(name: 'updated', type: 'datetime', nullable: true)]
    private $updated;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Assert\NotBlank(groups: ['save'])]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'activity_description', type: 'text', nullable: true)]
    #[Assert\NotBlank(groups: ['save'])]
    private $activityDescription;

    /**
     * @var string
     */
    #[ORM\Column(name: 'locationDescription', type: 'text', nullable: true)]
    private $locationDescription;

    /**
     * @var string
     */
    #[ORM\Column(name: 'usageRestriction', type: 'text', nullable: true)]
    private $usageRestriction;

    /**
     * @var string
     */
    #[ORM\Column(name: 'surface', type: 'float', nullable: true)]
    private $surface;

    /**
     * @var string
     */
    #[ORM\Column(name: 'address', type: 'string', length: 255, nullable: true)]
    private $address;

    /**
     * @var string
     *
     *
     */
    #[ORM\Column(name: 'zip_code', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['standard'])]
    #[Assert\Length(max: 5, min: 5, minMessage: 'Code postal invalide', maxMessage: 'Code postal invalide', groups: ['draft', 'standard'])]
    #[Assert\Regex(pattern: '/[0-9]{2}[0-9]{3}/', message: 'Code postal invalide', groups: ['draft', 'standard'])]
    private $zipCode;

    /**
     * @var string
     */
    #[ORM\Column(name: 'city', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['standard', 'multi_location'])]
    private $city;

    /**
     * @var string
     */
    #[ORM\Column(name: 'size', type: 'string', length: 255, nullable: true)]
    private $size;

    /**
     * @var string
     */
    #[ORM\Column(name: 'availability', type: 'string', length: 255, nullable: true)]
    private $availability;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'limitAvailability', type: 'datetime', nullable: true)]
    #[Assert\NotBlank(groups: ['standard'])]
    private $limitAvailability;

    #[ORM\Column(name: 'workflow_type', type: 'string', length: 30, options: ['default' => 'standard'])]
    private string $workflowType = self::WORKFLOW_STANDARD;

    #[ORM\OneToMany(targetEntity: SpaceLocation::class, mappedBy: 'space', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['displayOrder' => 'ASC', 'id' => 'ASC'])]
    private $locations;

    /**
     * @var string
     */
    #[ORM\Column(name: 'price', type: 'float', nullable: true)]
    private $price;

    /**
     * @var string
     */
    #[ORM\Column(name: 'price_text', type: 'string', length: 255, nullable: true)]
    private $priceText;

    #[ORM\OneToMany(targetEntity: \App\Entity\SpaceImage::class, mappedBy: 'space', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid]
    protected $pics;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class, inversedBy: 'spaces')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true)]
    #[Assert\NotBlank(groups: ['save'])]
    protected $owner;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'enabled', type: 'boolean')]
    private $enabled = false;

    /**
     * Active le mode "candidature au fil de l'eau" pour cet AAC.
     * Quand c'est actif, la candidature affiche/rend obligatoire la "Date d'entrée souhaitée".
     */
    #[ORM\Column(name: 'rolling_applications', type: 'boolean', options: ['default' => false])]
    private $rollingApplications = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'closed', type: 'boolean')]
    private $closed = false;

    #[ORM\OneToMany(targetEntity: \App\Entity\Parcel::class, mappedBy: 'space', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private $parcels;

    #[ORM\OneToMany(targetEntity: \App\Entity\SpaceDocument::class, mappedBy: 'space', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private $documents;

    #[ORM\OneToMany(targetEntity: \App\Entity\SpaceAttribute::class, mappedBy: 'space', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private $tags;

    #[ORM\ManyToOne(targetEntity: \App\Entity\SpaceType::class)]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id')]
    private $type;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean', name: 'is_submitted')]
    private $submitted = false;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', name: 'submitted_at', nullable: true)]
    private $submittedAt;

    #[ORM\OneToMany(targetEntity: \App\Entity\Application::class, mappedBy: 'space')]
    private $application;

    #[ORM\OneToMany(targetEntity: \App\Entity\SpaceVisit::class, mappedBy: 'space', cascade: ['persist', 'remove'])]
    private $visits;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nb_spaces', type: 'integer', nullable: true)]
    #[Assert\NotBlank(groups: ['standard'])]
    private $nbSpaces;

    /**
     * @var int
     */
    #[ORM\Column(name: 'min_space', type: 'integer', nullable: true)]
    #[Assert\NotBlank(groups: ['standard'])]
    private $minSpace;

    /**
     * @var int
     */
    #[ORM\Column(name: 'max_space', type: 'integer', nullable: true)]
    #[Assert\NotBlank(groups: ['standard'])]
    private $maxSpace;

    /**
     * @var string
     */
    #[ORM\Column(name: 'societaire_message_type', type: 'string', length: 50, nullable: true)]
    private $societaireMessageType;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'managed_by_label', type: 'string', length: 255, nullable: true)]
    private $managedByLabel;

    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param SpaceAttribute $tag
     *
     * @return $this
     */
    public function addTag($tag)
    {
        $this->setUpdated(new \DateTime());
        $tag->setSpace($this);
        $this->tags[] = $tag;
    }

    /**
     * @param SpaceAttribute $tag
     */
    public function removeTag($tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Space constructor.
     */
    public function __construct()
    {
        $this->pics = new ArrayCollection();
        $this->parcels = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->visits = new ArrayCollection();
        $this->locations = new ArrayCollection();
    }
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
     * @return bool
     */
    public function getIsErp()
    {
        return (bool) $this->isErp;
    }

    /**
     * @return bool
     */
    public function isErp()
    {
        return $this->getIsErp();
    }

    /**
     * @param bool $isErp
     * @return Space
     */
    public function setIsErp($isErp)
    {
        $this->isErp = (bool) $isErp;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Space
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
     * Set description.
     *
     * @param string $description
     *
     * @return Space
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set activityDescription.
     *
     * @param string $activityDescription
     *
     * @return Space
     */
    public function setActivityDescription($activityDescription)
    {
        $this->activityDescription = $activityDescription;

        return $this;
    }

    /**
     * Get activityDescription.
     *
     * @return string
     */
    public function getActivityDescription()
    {
        return $this->activityDescription;
    }


    /**
     * Set locationDescription.
     *
     * @param string $locationDescription
     *
     * @return Space
     */
    public function setLocationDescription($locationDescription)
    {
        $this->locationDescription = $locationDescription;

        return $this;
    }

    /**
     * Get locationDescription.
     *
     * @return string
     */
    public function getLocationDescription()
    {
        return $this->locationDescription;
    }

    /**
     * Set usageRestriction.
     *
     * @param string $usageRestriction
     *
     * @return Space
     */
    public function setUsageRestriction($usageRestriction)
    {
        $this->usageRestriction = $usageRestriction;

        return $this;
    }

    /**
     * Get usageRestriction.
     *
     * @return string
     */
    public function getUsageRestriction()
    {
        return $this->usageRestriction;
    }

    /**
     * Set surface.
     *
     * @param string $surface
     *
     * @return Space
     */
    public function setSurface($surface)
    {
        $this->surface = $surface;

        return $this;
    }

    /**
     * Get surface.
     *
     * @return string
     */
    public function getSurface()
    {
        return $this->surface;
    }

    /**
     * Set size.
     *
     * @param string $size
     *
     * @return Space
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set availability.
     *
     * @param string $availability
     *
     * @return Space
     */
    public function setAvailability($availability)
    {
        $this->availability = $availability;

        return $this;
    }

    /**
     * Get availability.
     *
     * @return string
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * Set limitAvailability.
     *
     * @param \DateTime $limitAvailability
     *
     * @return Space
     */
    public function setLimitAvailability($limitAvailability)
    {
        $this->limitAvailability = $limitAvailability;

        return $this;
    }

    /**
     * Get limitAvailability.
     *
     * @return \DateTime
     */
    public function getLimitAvailability()
    {
        return $this->limitAvailability;
    }

    /**
     * Set price.
     *
     * @param float $price
     *
     * @return Space
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set priceText.
     *
     * @param string $priceText
     *
     * @return Space
     */
    public function setPriceText($priceText)
    {
        $this->priceText = $priceText;

        return $this;
    }

    /**
     * Get priceText.
     *
     * @return string
     */
    public function getPriceText()
    {
        return $this->priceText;
    }

    /**
     * @return ArrayCollection|SpaceImage[]
     */
    public function getPics()
    {
        $pics = [];

        foreach ($this->pics as $p) {
            if ($p->getFileType() === SpaceImage::FILETYPE_IMAGE || $p->getFileType() === null) {
                $pics[] = $p;
            }
        }

        return $pics;
    }

    /**
     * @return ArrayCollection|SpaceImage[]
     */
    public function getDocs($type = null)
    {
        $docs = [];

        foreach ($this->pics as $p) {
            if ($p->getFileType() !== SpaceImage::FILETYPE_IMAGE) {
                $docs[] = $p;
            }
        }

        if ($type) {
            $docs = array_values(array_filter($docs, fn($doc) => $doc->getFileType() === $type));
        }

        return $docs;
    }

    /**
     * Add pics.
     *
     * @param SpaceImage $pic
     *
     * @return Space
     */
    public function addPic(SpaceImage $pic)
    {
        $pic->setSpace($this);
        $pic->setFileType(SpaceImage::FILETYPE_IMAGE);
        $this->pics->add($pic);

        $this->setUpdated(new \DateTime());

        return $this;
    }

    /**
     * Add doc.
     *
     * @param SpaceImage $doc
     *
     * @return Space
     */
    public function addDoc(SpaceImage $doc, $type)
    {
        $doc->setSpace($this);
        $doc->setFileType($type);
        $doc->setPosition($this->pics->count());
        $this->pics->add($doc);

        $this->setUpdated(new \DateTime());

        return $this;
    }

    /**
     * Remove pics.
     *
     * @param \App\Entity\SpaceImage $pics
     */
    public function removePic(SpaceImage $pics)
    {
        $this->pics->removeElement($pics);
        $this->setUpdated(new \DateTime());
    }

    /**
     * Remove docs.
     *
     * @param \App\Entity\SpaceImage $docs
     */
    public function removeDoc(SpaceImage $docs)
    {
        $this->pics->removeElement($docs);
        $this->setUpdated(new \DateTime());
    }

    /**
     * Virtual getter for Sonata admin — returns the first AAC document, or null.
     */
    public function getDocAac(): ?SpaceImage
    {
        $docs = $this->getDocs(SpaceImage::FILETYPE_DOCUMENT_AAC);
        return !empty($docs) ? $docs[0] : null;
    }

    /**
     * Virtual setter for Sonata admin — adds a new AAC doc if a file was uploaded.
     * If the SpaceImage is already in the collection (no new upload), does nothing.
     */
    public function setDocAac(?SpaceImage $doc): self
    {
        if ($doc === null || ($doc->getFile() === null && $doc->getFileName() === null)) {
            foreach ($this->pics as $pic) {
                if ($pic->getFileType() === SpaceImage::FILETYPE_DOCUMENT_AAC) {
                    $this->pics->removeElement($pic);
                }
            }
            return $this;
        }

        if ($doc->getFile() !== null) {
            foreach ($this->pics as $pic) {
                if ($pic->getFileType() === SpaceImage::FILETYPE_DOCUMENT_AAC && $pic !== $doc) {
                    $this->pics->removeElement($pic);
                }
            }
            if (!$this->pics->contains($doc)) {
                $this->addDoc($doc, SpaceImage::FILETYPE_DOCUMENT_AAC);
            }
        }

        return $this;
    }

    /**
     * Virtual getter for Sonata admin — returns the first Plan document, or null.
     */
    public function getDocPlan(): ?SpaceImage
    {
        $docs = $this->getDocs(SpaceImage::FILETYPE_DOCUMENT_PLAN);
        return !empty($docs) ? $docs[0] : null;
    }

    /**
     * Virtual setter for Sonata admin — adds a new Plan doc if a file was uploaded.
     */
    public function setDocPlan(?SpaceImage $doc): self
    {
        if ($doc === null || ($doc->getFile() === null && $doc->getFileName() === null)) {
            foreach ($this->pics as $pic) {
                if ($pic->getFileType() === SpaceImage::FILETYPE_DOCUMENT_PLAN) {
                    $this->pics->removeElement($pic);
                }
            }
            return $this;
        }

        if ($doc->getFile() !== null) {
            foreach ($this->pics as $pic) {
                if ($pic->getFileType() === SpaceImage::FILETYPE_DOCUMENT_PLAN && $pic !== $doc) {
                    $this->pics->removeElement($pic);
                }
            }
            if (!$this->pics->contains($doc)) {
                $this->addDoc($doc, SpaceImage::FILETYPE_DOCUMENT_PLAN);
            }
        }

        return $this;
    }

    /**
     * Virtual getter for Sonata admin — returns the first FAQ document, or null.
     */
    public function getDocFaq(): ?SpaceImage
    {
        $docs = $this->getDocs(SpaceImage::FILETYPE_DOCUMENT_FAQ);
        return !empty($docs) ? $docs[0] : null;
    }

    /**
     * Virtual setter for Sonata admin — adds a new FAQ doc if a file was uploaded.
     */
    public function setDocFaq(?SpaceImage $doc): self
    {
        if ($doc === null || ($doc->getFile() === null && $doc->getFileName() === null)) {
            foreach ($this->pics as $pic) {
                if ($pic->getFileType() === SpaceImage::FILETYPE_DOCUMENT_FAQ) {
                    $this->pics->removeElement($pic);
                }
            }
            return $this;
        }

        if ($doc->getFile() !== null) {
            foreach ($this->pics as $pic) {
                if ($pic->getFileType() === SpaceImage::FILETYPE_DOCUMENT_FAQ && $pic !== $doc) {
                    $this->pics->removeElement($pic);
                }
            }
            if (!$this->pics->contains($doc)) {
                $this->addDoc($doc, SpaceImage::FILETYPE_DOCUMENT_FAQ);
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return bool
     * @phpstan-impure
     */
    public function isClosed()
    {
        if ($this->closed) {
            return true;
        }
        if ($this->isMultiLocation()) {
            return false;
        }
        $limit = $this->getLimitAvailability();
        if ($limit !== null && $limit < new \DateTime('today')) {
            return true;
        }

        return false;
    }

    public function getWorkflowType(): string
    {
        return $this->workflowType;
    }

    public function setWorkflowType(string $workflowType): self
    {
        $this->workflowType = $workflowType;

        return $this;
    }

    public function isMultiLocation(): bool
    {
        return $this->workflowType === self::WORKFLOW_MULTI_LOCATION;
    }

    public function getListingCardClass(): string
    {
        if ($this->isMultiLocation()) {
            return 'space-card--multi-location';
        }

        return $this->isProposedByAdmin() ? 'space-card--by-admin' : 'space-card--by-proprio';
    }

    /**
     * @return ArrayCollection<int, SpaceLocation>
     */
    public function getLocations()
    {
        return $this->locations;
    }

    public function addLocation(SpaceLocation $location): self
    {
        if (!$this->locations->contains($location)) {
            $location->setSpace($this);
            $this->locations->add($location);
            $this->setUpdated(new \DateTime());
        }

        return $this;
    }

    public function removeLocation(SpaceLocation $location): void
    {
        if ($this->locations->removeElement($location)) {
            $this->setUpdated(new \DateTime());
        }
    }

    /**
     * @return SpaceLocation[]
     */
    public function getActiveLocations(): array
    {
        return $this->locations->filter(static fn (SpaceLocation $location) => !$location->isSuspended())->toArray();
    }

    /**
     * @return SpaceLocation[]
     */
    public function getOrderedActiveLocations(): array
    {
        $locations = $this->getActiveLocations();
        usort($locations, static fn (SpaceLocation $a, SpaceLocation $b): int => $a->getDisplayOrder() <=> $b->getDisplayOrder());

        return $locations;
    }

    public function getDisplayCity(): ?string
    {
        if ($this->isMultiLocation()) {
            return $this->city ?: null;
        }

        if ($this->city) {
            return $this->city;
        }
        $first = $this->locations->first();

        return $first instanceof SpaceLocation ? $first->getCity() : null;
    }

    public function getDisplayZipCode(): ?string
    {
        if ($this->isMultiLocation()) {
            return $this->zipCode ?: null;
        }

        if ($this->zipCode) {
            return $this->zipCode;
        }
        $first = $this->locations->first();

        return $first instanceof SpaceLocation ? $first->getZipCode() : null;
    }

    /**
     * @return bool
     */
    public function getClosed()
    {
        return $this->closed;
    }

    /**
     * @param bool $closed
     */
    public function setClosed($closed)
    {
        $this->closed = $closed;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return \Datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \Datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \Datetime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @param \Datetime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeTimestampsOnCreate(): void
    {
        $now = new \DateTime();
        if ($this->created === null) {
            $this->created = $now;
        }
        if ($this->updated === null) {
            $this->updated = $now;
        }
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedTimestamp(): void
    {
        $this->updated = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getParcels()
    {
        return $this->parcels;
    }

    /**
     * @param mixed $parcels
     */
    public function setParcels($parcels)
    {
        $this->parcels = $parcels;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $parcels
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param mixed $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function addParcel(Parcel $parcel)
    {
        $this->setUpdated(new \DateTime());
        $this->parcels->add($parcel);

        $parcel->setSpace($this);

        return $this;
    }

    /**
     * @param mixed $parcels
     */
    public function removeParcel($parcel)
    {
        $this->parcels->removeElement($parcel);
    }

    /**
     * @param SpaceType
     *
     * @return Parcel
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return SpaceType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public  function __toString(): string
    {
        return $this->getName().' - '.($this->getOwner() != null ? $this->getOwner()->getCompany() : '');
    }

    /**
     * @return int
     */
    public function getMinSize() {
        $min = -1;
        foreach ($this->getParcels() as $parcel) {
            if ($min == -1 || $min > $parcel->getMinSurface()) {
                $min = $parcel->getMinSurface();
            }
        }
        return $min;
    }

    /**
     * @return int
     */
    public function getMaxSize() {
        $max = 0;
        foreach ($this->getParcels() as $parcel) {
            if ($max < $parcel->getMaxSurface()) {
                $max = $parcel->getMaxSurface();
            }
        }
        return $max;
    }

    /**
     * @return boolean
     */
    public function isSubmitted()
    {
        return $this->submitted;
    }

    /**
     * @param boolean $submitted
     */
    public function setSubmitted($submitted)
    {
        // N'horodate la soumission qu'au premier passage à true,
        // pour ne pas écraser la date lors d'une dépublication (false).
        if ($submitted && $this->submittedAt === null) {
            $this->submittedAt = new \DateTime();
        }
        $this->submitted = $submitted;
    }

    /**
     * @return \DateTime
     */
    public function getSubmittedAt()
    {
        return $this->submittedAt;
    }

    public function isOwner(?UserInterface $user): bool
    {
        if ($user === null || !$user instanceof User || !$this->owner) {
            return false;
        }

        return $this->owner->getId() === $user->getId();
    }

    /**
     * Indique si l'AAC a été proposé par un administrateur (owner avec ROLE_ADMIN ou typeUser admin).
     *
     * @return bool
     */
    public function isProposedByAdmin()
    {
        if (!$this->owner) {
            return false;
        }
        $roles = $this->owner->getRoles();
        // Vérifier le rôle admin (FOSUser groups ou typeUser)
        // On vérifie ROLE_SUPER_ADMIN aussi car getRoles() peut ne pas retourner la hiérarchie complète
        return in_array('ROLE_ADMIN', $roles, true)
            || in_array('ROLE_SUPER_ADMIN', $roles, true)
            || $this->owner->getTypeUser() === User::ADMIN;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return $this->enabled && $this->submitted;
    }

    /**
     * @return string
     */
    public function getDepCode() {
        $zipCode = $this->getDisplayZipCode();
        if (!$zipCode) {
            return '';
        }
        return substr($zipCode, 0, 2);
    }

    #[Assert\Callback(groups: ['save'])]
    public function validatePrice(ExecutionContextInterface $context)
    {
        if (empty($this->price) && empty($this->priceText)) {
            $context->buildViolation('Vous devez renseigner soit le prix au m² mensuel, soit le prix personnalisé.')
                ->atPath('price')
                ->addViolation();
        }
    }

    /**
     * @return mixed
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param mixed $application
     */
    public function setApplication($application)
    {
        $this->application = $application;
    }

    /**
     * @param $type
     * @return bool
     */
    public function hasTagType($type) {
        foreach ($this->tags as $tag) {
            if ($tag->getAvailability() == $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * Détermine si l'AAC est "au fil de l'eau".
     * On se base sur le nom de l'attribut (ex: "au fil de l'eau", "fil de l'eau", etc.)
     * et uniquement quand le tag est marqué "Inclus".
     *
     * @return bool
     */
    public function isRollingAAC()
    {
        if ($this->rollingApplications) {
            return true;
        }

        foreach ($this->tags as $tag) {
            if (!$tag || !$tag->isIncluded() || !$tag->getAttribute()) {
                continue;
            }
            $name = (string) $tag->getAttribute()->getName();
            $normalized = $this->normalizeTagName($name);
            if ($normalized === '') {
                continue;
            }

            // Règle actuelle : “au fil de l’eau”
            if (str_contains($normalized, 'fil de l eau') || str_contains($normalized, 'fil de leau')) {
                return true;
            }

            // Compat : certains espaces peuvent encore utiliser un tag “indéfini”
            if (str_contains($normalized, 'indefini')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Alias compat : ancienne règle “indéfini”.
     *
     * @return bool
     */
    public function isIndefiniteAAC()
    {
        return $this->isRollingAAC();
    }

    public function requiresStartOccupation(): bool
    {
        return $this->isMultiLocation() || $this->isRollingAAC();
    }

    public function isStartOccupationRequired(): bool
    {
        return $this->requiresStartOccupation();
    }

    /**
     * @return bool
     */
    public function getRollingApplications()
    {
        return (bool) $this->rollingApplications;
    }

    /**
     * @param bool $rollingApplications
     * @return $this
     */
    public function setRollingApplications($rollingApplications)
    {
        $this->rollingApplications = (bool) $rollingApplications;
        return $this;
    }

    /**
     * @param string $name
     * @return string
     */
    private function normalizeTagName($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }
        $name = mb_strtolower($name, 'UTF-8');
        // Retirer les accents pour matcher "indéfini" = "indefini"
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
            if ($converted !== false) {
                $name = $converted;
            }
        }
        // Remplacer la ponctuation par des espaces, puis normaliser
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', (string) $name);
        $name = trim((string) $name);
        return $name;
    }

    /**
     * @param $type
     * @return int
     */
    public function nbApplication($type) {
        $ret = 0;

        $criteria = Criteria::create()->where(Criteria::expr()->neq("status", Application::DRAFT_STATUS));

        if($type == null){
          return count($this->getApplication()->matching($criteria));
        }

        foreach ($this->getApplication()->matching($criteria) as $app) {
            if ($app->getStatus() == $type) {
                $ret++;
            }
        }

        return $ret;
    }

    /**
     * @param $type
     * @return int
     */
    public function nbValidApplication() {
        $criteria = Criteria::create()->where(Criteria::expr()->neq("status", Application::DRAFT_STATUS));

        $ret = $this->getApplication()->matching($criteria)->count();

        return $ret;
    }

    /**
     * @param $useType
     * @return int
     */
    public function nbApplicationUseType($useType) {
        $ret = 0;

        $criteria = Criteria::create()->where(Criteria::expr()->neq("status", Application::DRAFT_STATUS));

        foreach ($this->getApplication()->matching($criteria) as $app) {
          if ($app->getProjectHolder()->getUseType()) {
            if ($app->getProjectHolder()->getUseType()->getId() == $useType->getId()) {
              $ret++;
            }
          }
        }

        return $ret;
    }

    /**
     * @param $category
     * @return int
     */
    public function nbApplicationCategory($category) {
        $ret = 0;

        $criteria = Criteria::create()->where(Criteria::expr()->neq("status", Application::DRAFT_STATUS));

        foreach ($this->getApplication()->matching($criteria) as $app) {
            if ($app->getCategory() !== null && $app->getCategory()->getId() == $category->getId()) {
                $ret++;
            }
        }

        return $ret;
    }

    /**
     * @return int
     */
    public function getTotalWishedSize() {
        $ret = 0;

        $criteria = Criteria::create()->where(Criteria::expr()->neq("status", Application::DRAFT_STATUS));

        foreach ($this->getApplication()->matching($criteria) as $app) {
            $ret += $app->getWishedSize();
        }

        return $ret;
    }

    /**
     * Nombre de candidatures d'une catégorie filtrées par statut
     *
     * @param $category
     * @param $status
     * @return int
     */
    public function nbApplicationCategoryByStatus($category, $status) {
        $ret = 0;

        $criteria = Criteria::create()->where(Criteria::expr()->neq("status", Application::DRAFT_STATUS));

        foreach ($this->getApplication()->matching($criteria) as $app) {
            if ($app->getCategory() !== null && $app->getCategory()->getId() == $category->getId() && $app->getStatus() == $status) {
                $ret++;
            }
        }

        return $ret;
    }

    /**
     * Nombre de candidatures (hors brouillons) pour un statut juridique (Application.companyStatus) donné.
     *
     * @param string $companyStatus
     * @return int
     */
    public function nbApplicationByCompanyStatus($companyStatus) {
        $ret = 0;
        $criteria = Criteria::create()->where(Criteria::expr()->neq("status", Application::DRAFT_STATUS));
        foreach ($this->getApplication()->matching($criteria) as $app) {
            if ($app->getCompanyStatus() === $companyStatus) {
                $ret++;
            }
        }
        return $ret;
    }

    /**
     * Nombre de candidatures (hors brouillons) ayant renseigné une surface > 0 pour une catégorie.
     * Utilisé pour calculer une moyenne correcte (exclut les candidatures sans surface).
     *
     * @param $category
     * @return int
     */
    public function nbApplicationCategoryWithSurface($category) {
        $ret = 0;
        $criteria = Criteria::create()->where(Criteria::expr()->neq("status", Application::DRAFT_STATUS));
        foreach ($this->getApplication()->matching($criteria) as $app) {
            if ($app->getCategory() !== null && $app->getCategory()->getId() == $category->getId() && $app->getWishedSize() > 0) {
                $ret++;
            }
        }
        return $ret;
    }

    /**
     * Surface totale demandée pour une catégorie donnée
     *
     * @param $category
     * @return int
     */
    public function totalWishedSizeByCategory($category) {
        $ret = 0;

        $criteria = Criteria::create()->where(Criteria::expr()->neq("status", Application::DRAFT_STATUS));

        foreach ($this->getApplication()->matching($criteria) as $app) {
            if ($app->getCategory() !== null && $app->getCategory()->getId() == $category->getId()) {
                $ret += $app->getWishedSize();
            }
        }

        return $ret;
    }

    /**
     * @param ExecutionContextInterface $context
     */
    #[Assert\Callback(groups: ['save'])]
    public function validateNbParcels(ExecutionContextInterface $context)
    {
        // La gestion des lots (parcels) a été retirée du formulaire front-end.
        // Validation désactivée intentionnellement.
    }

    /**
     * @param ExecutionContextInterface $context
     */
    #[Assert\Callback(groups: ['save'])]
    public function validatePicturesCount(ExecutionContextInterface $context)
    {
        if ($this->pics->count() > self::MAX_PICTURES_UPLOAD) {
            $context ->buildViolation('Vous ne pouvez ajouter que {{ nb }} photos maximum')
                     ->atPath('pics')
                     ->setParameter('{{ nb }}', self::MAX_PICTURES_UPLOAD)
                     ->addViolation();
        }
    }

    /**
     * @param ExecutionContextInterface $context
     */
    #[Assert\Callback(groups: ['save'])]
    public function validateDocs(ExecutionContextInterface $context)
    {
        if (count($this->getDocs(SpaceImage::FILETYPE_DOCUMENT_AAC)) < 1) {
            $context->buildViolation('Il manque le document de l\'appel à candidature')
                    ->atPath('doc_aac')
                    //->setParameter('{{ value }}', $invalidValue)
                    ->addViolation();
        }

        // Le document "Répartition des espaces" n'est plus obligatoire
        // if (count($this->getDocs(SpaceImage::FILETYPE_DOCUMENT_PLAN)) < 1) {
        //     $context->buildViolation('Il manque le document de plan')
        //             ->atPath('doc_plan')
        //             //->setParameter('{{ value }}', $invalidValue)
        //             ->addViolation();
        // }
    }

    #[Assert\Callback(groups: ['save'])]
    public function validateMultiLocationLocations(ExecutionContextInterface $context): void
    {
        if (!$this->isMultiLocation()) {
            return;
        }

        $activeLocations = 0;
        foreach ($this->locations as $location) {
            if ($location instanceof SpaceLocation && trim((string) $location->getName()) !== '') {
                $activeLocations++;
            }
        }

        if ($activeLocations < 1) {
            $context->buildViolation('Au moins un site complet (nom, ville, code postal) est requis pour publier.')
                ->atPath('locations')
                ->addViolation();
        }
    }

    #[Assert\Callback(groups: ['save'])]
    public function validateAvailability(ExecutionContextInterface $context): void
    {
        if ($this->isMultiLocation()) {
            return;
        }

        if (trim((string) $this->getAvailability()) === '') {
            $context->buildViolation('Cette valeur ne doit pas être vide.')
                ->atPath('availability')
                ->addViolation();
        }
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Get submitted
     *
     * @return boolean
     */
    public function getSubmitted()
    {
        return $this->submitted;
    }

    /**
     * Set submittedAt
     *
     * @param \DateTime $submittedAt
     * @return Space
     */
    public function setSubmittedAt($submittedAt)
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    /**
     * Add documents
     *
     * @param \App\Entity\SpaceDocument $documents
     * @return Space
     */
    public function addDocument(\App\Entity\SpaceDocument $documents)
    {
        $this->documents[] = $documents;

        return $this;
    }

    /**
     * Remove documents
     *
     * @param \App\Entity\SpaceDocument $documents
     */
    public function removeDocument(\App\Entity\SpaceDocument $documents)
    {
        $this->documents->removeElement($documents);
    }

    /**
     * Get documents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Add application
     *
     * @param \App\Entity\Application $application
     * @return Space
     */
    public function addApplication(\App\Entity\Application $application)
    {
        $this->application[] = $application;

        return $this;
    }

    /**
     * Remove application
     *
     * @param \App\Entity\Application $application
     */
    public function removeApplication(\App\Entity\Application $application)
    {
        $this->application->removeElement($application);
    }

    /**
     * @return int
     */
    public function getNbSpaces()
    {
        return $this->nbSpaces;
    }

    /**
     * @param int $nbSpaces
     */
    public function setNbSpaces($nbSpaces)
    {
        $this->nbSpaces = $nbSpaces;
    }

    /**
     * @return int
     */
    public function getMinSpace()
    {
        return $this->minSpace;
    }

    /**
     * @param int $minSpace
     */
    public function setMinSpace($minSpace)
    {
        $this->minSpace = $minSpace;
    }

    /**
     * @return int
     */
    public function getMaxSpace()
    {
        return $this->maxSpace;
    }

    /**
     * @param int $maxSpace
     */
    public function setMaxSpace($maxSpace)
    {
        $this->maxSpace = $maxSpace;
    }

    /**
     * @return string
     */
    public function getSocietaireMessageType()
    {
        return $this->societaireMessageType;
    }

    /**
     * @param string $societaireMessageType
     */
    public function setSocietaireMessageType($societaireMessageType)
    {
        $this->societaireMessageType = $societaireMessageType;
    }

    /**
     * @param string|null $managedByLabel
     * @return Space
     */
    public function setManagedByLabel($managedByLabel)
    {
        $this->managedByLabel = $managedByLabel;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagedByLabel(): ?string
    {
        return $this->managedByLabel ?: null;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVisits()
    {
        return $this->visits;
    }

    /**
     * @param SpaceVisit $visit
     * @return $this
     */
    public function addVisit(SpaceVisit $visit)
    {
        $visit->setSpace($this);
        $this->visits->add($visit);
        return $this;
    }

    /**
     * @param SpaceVisit $visit
     */
    public function removeVisit(SpaceVisit $visit)
    {
        $this->visits->removeElement($visit);
    }
}
