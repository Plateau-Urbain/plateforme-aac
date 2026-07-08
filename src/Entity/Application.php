<?php

namespace App\Entity;

use App\Entity\ApplicationFile;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Application
 */
#[ORM\Entity(repositoryClass: \App\Repository\ApplicationRepository::class)]
#[ORM\Table]
#[ORM\HasLifecycleCallbacks]
class Application
{
    const DAY_TYPE   = "jours";
    const WEEK_TYPE  = "semaines";
    const MONTH_TYPE = "mois";
    const YEAR_TYPE  = "an(s)";

    const DRAFT_STATUS  = 'draft';
    const UNREAD_STATUS  = 'unread';
    const WAIT_STATUS   = "awaiting";
    const ACCEPT_STATUS = "accepted";
    const REJECT_STATUS = "rejected";

    /**
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            self::DRAFT_STATUS => 'Brouillon',
            self::UNREAD_STATUS => 'Non lue',
            self::WAIT_STATUS => 'En attente',
            self::ACCEPT_STATUS => 'Accepté',
            self::REJECT_STATUS => 'Refusé',
        ];
    }

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
    #[ORM\Column(name: 'status', type: 'string')]
    private $status = self::DRAFT_STATUS;

    #[ORM\Column(name: 'selected', type: 'boolean')]
    private $selected = false;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', nullable: true)]
    #[Assert\NotBlank(groups: ['submit'])]
    private $name;

    /**
     * Statut juridique simplifié saisi au moment de la candidature
     * (distinct de User::$companyStatus, liste restreinte pour l'équipe projet).
     *
     * @var string
     */
    #[ORM\Column(name: 'company_status', type: 'string', length: 64, nullable: true)]
    #[Assert\NotBlank(groups: ['submit'])]
    private $companyStatus;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Assert\NotBlank(groups: ['submit'])]
    private $description;

    /**
     * Quel sera l'usage du local ?
     */
    #[ORM\Column(name: 'local_usage_description', type: 'text', nullable: true)]
    private $localUsageDescription;

    /**
     * @var string
     */
    #[ORM\Column(name: 'contribution', type: 'text', nullable: true)]
    private $contribution;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'start_occupation', type: 'date', nullable: true)]
    private $startOccupation;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'length_occupation', type: 'integer', nullable: true)]
    #[Assert\Type(type: 'integer', message: "La valeur {{ value }} n'est pas un nombre entier valide.")]
    #[Assert\Range(min: 0, minMessage: 'Vous devez obligatoirement renseigner une durée positive.', groups: ['projectHolder'])]
    private $lengthOccupation;

    /**
     * @var string
     */
    #[ORM\Column(name: 'length_type_occupation', type: 'string', length: 15, nullable: true)]
    private $lengthTypeOccupation;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Space::class, inversedBy: 'application')]
    private $space;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Category::class)]
    private $category;

    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class, inversedBy: 'applications', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'projectHolder_id', referencedColumnName: 'id', nullable: true)]
    private $projectHolder;

    #[ORM\OneToMany(targetEntity: \App\Entity\ApplicationFile::class, mappedBy: 'application', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    protected $files;

    #[ORM\Column(name: 'created', type: 'datetime')]
    private $created;

    #[ORM\Column(name: 'updated', type: 'datetime', nullable: true)]
    private $updated;

    #[ORM\Column(name: 'wishedSize', type: 'integer', nullable: true)]
    #[Assert\NotBlank(groups: ['submit'])]
    #[Assert\Type(type: 'integer', message: "La valeur {{ value }} n'est pas un nombre entier valide.", groups: ['projectHolder', 'default', 'submit'])]
    #[Assert\Range(min: 1, minMessage: 'Veuillez entrer un entier positif (ex : 12).', groups: ['projectHolder', 'default', 'submit'])]
    protected $wishedSize;

    /**
     * Validation conditionnelle : la "date d'entrée souhaitée" n'est requise que
     * si l'AAC est tagguée "indéfini".
     */
    #[Assert\Callback(groups: ['submit'])]
    public function validateStartOccupationForIndefiniteAAC(ExecutionContextInterface $context)
    {
        $space = $this->getSpace();
        if (!$space) {
            return;
        }

        $isRolling = false;
        if (method_exists($space, 'isRollingAAC')) {
            $isRolling = (bool) $space->isRollingAAC();
        } elseif (method_exists($space, 'isIndefiniteAAC')) {
            // Compat ancien nom
            $isRolling = (bool) $space->isIndefiniteAAC();
        }

        if (!$isRolling) {
            return;
        }

        if ($this->startOccupation === null) {
            $context->buildViolation('Veuillez indiquer une date d\'entrée souhaitée.')
                ->atPath('startOccupation')
                ->addViolation();
        }
    }

    #[ORM\Column(name: 'openToGlobalProject', type: 'boolean', nullable: true)]
    protected $openToGlobalProject = false;

    #[ORM\Column(name: 'devenirSocietaire', type: 'boolean', nullable: true)]
    protected $devenirSocietaire = false;

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->lengthTypeOccupation = 'mois';
    }

    /**
     * @return \Datetime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \Datetime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

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
     * Set description
     *
     * @param string $description
     * @return Application
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $localUsageDescription
     * @return Application
     */
    public function setLocalUsageDescription($localUsageDescription)
    {
        $this->localUsageDescription = $localUsageDescription;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocalUsageDescription()
    {
        return $this->localUsageDescription;
    }

    /**
     * Set description
     *
     * @param string $contribution
     * @return Application
     */
    public function setContribution($contribution)
    {
        $this->contribution = $contribution;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getContribution()
    {
        return $this->contribution;
    }
    /**
     * Set startOccupation
     *
     * @param \DateTime $startOccupation
     * @return Application
     */
    public function setStartOccupation($startOccupation)
    {
        $this->startOccupation = $startOccupation;

        return $this;
    }

    /**
     * Get startOccupation
     *
     * @return \DateTime
     */
    public function getStartOccupation()
    {
        return $this->startOccupation;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return \App\Entity\User|null
     */
    public function getProjectHolder(): ?\App\Entity\User
    {
        return $this->projectHolder;
    }

    /**
     * @param mixed $projectHolder
     */
    public function setProjectHolder($projectHolder)
    {
        $this->projectHolder = $projectHolder;
    }

    /**
     * @return \App\Entity\Space|null
     */
    public function getSpace(): ?\App\Entity\Space
    {
        return $this->space;
    }

    /**
     * @param mixed $space
     */
    public function setSpace($space)
    {
        $this->space = $space;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLengthOccupation()
    {
        return $this->lengthOccupation;
    }

    /**
     * @param string $lengthOccupation
     */
    public function setLengthOccupation($lengthOccupation)
    {
        $this->lengthOccupation = $lengthOccupation;
    }

    /**
     * @return string
     */
    public function getLengthTypeOccupation()
    {
        return $this->lengthTypeOccupation ?: 'mois';
    }

    /**
     * @param string $lengthTypeOccupation
     */
    public function setLengthTypeOccupation($lengthTypeOccupation)
    {
        $this->lengthTypeOccupation = $lengthTypeOccupation;
    }

    /**
     * @return ArrayCollection|ApplicationFile[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param ApplicationFile $file
     */
    public function addFile(ApplicationFile $file)
    {
        $file->setApplication($this);
        $this->files->add($file);
    }

    /**
     * Has file
     *
     * @return mixed
     */
    public function hasFileType($typeId)
    {
      foreach ($this->files as $file) {
        if($file->getSpaceDocument() != null && $file->getSpaceDocument()->getId() == $typeId){
          return true;
        } elseif($file->getSpaceDocument() == null && $typeId == null) {
          return true;
        }
      }

      return false;
    }

    /**
     * Has document type
     *
     * @return mixed
     */
    public function getFilesType($typeId)
    {
      $files = [];

      foreach ($this->files as $file) {
        if($file->getSpaceDocument() && $file->getSpaceDocument()->getId() == $typeId){
          $files[] = $file;
        } elseif(!$file->getSpaceDocument() && $typeId == null) {
          $files[] = $file;
        }
      }

      return $files;
    }


    /**
     * @param ApplicationFile $file
     */
    public function removeFile(ApplicationFile $file)
    {
        $this->files->removeElement($file);
    }

    /**
     * @return \Datetime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \Datetime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;

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
     * @return array
     */
    public static function getAllLengthType() {
        return [
            self::YEAR_TYPE  => self::YEAR_TYPE,
            self::MONTH_TYPE => self::MONTH_TYPE,
            self::WEEK_TYPE  => self::WEEK_TYPE,
            self::DAY_TYPE   => self::DAY_TYPE
        ];
    }

    /**
     * Liste restreinte de statuts juridiques utilisée côté candidature
     * (différente et plus courte que User::getAllProCompanyStatut()).
     *
     * @return array
     */
    public static function getApplicationCompanyStatuses()
    {
        return [
            'Association'        => 'Association',
            'Artiste'            => 'Artiste',
            'Entreprise'         => 'Entreprise',
            'Structure publique' => 'Structure publique',
        ];
    }

    /**
     * @return string|null
     */
    public function getCompanyStatus()
    {
        return $this->companyStatus;
    }

    /**
     * @param string|null $companyStatus
     * @return Application
     */
    public function setCompanyStatus($companyStatus)
    {
        $this->companyStatus = $companyStatus;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWishedSize()
    {
        return $this->wishedSize;
    }

    /**
     * @param mixed $wishedSize
     */
    public function setWishedSize($wishedSize)
    {
        $this->wishedSize = $wishedSize;
    }


    /**
     * @return mixed
     */
    public function getOpenToGlobalProject()
    {
        return $this->openToGlobalProject;
    }

    /**
     * @param mixed $openToGlobalProject
     */
    public function setOpenToGlobalProject($openToGlobalProject)
    {
        $this->openToGlobalProject = $openToGlobalProject;
    }

    /**
     * @return mixed
     */
    public function getDevenirSocietaire()
    {
        return $this->devenirSocietaire;
    }

    /**
     * @param mixed $devenirSocietaire
     */
    public function setDevenirSocietaire($devenirSocietaire)
    {
        $this->devenirSocietaire = $devenirSocietaire;
    }

    /**
     * @return bool
     */
    public function isAwaiting()
    {
        return $this->status === self::WAIT_STATUS;
    }

    /**
     * @return string
     */
    public function isDraft()
    {
        return $this->status === self::DRAFT_STATUS;
    }

    /**
     * @return bool
     */
    public function isAccepted()
    {
        return $this->status === self::ACCEPT_STATUS;
    }

    /**
     * @return bool
     */
    public function isRejected()
    {
        return $this->status === self::REJECT_STATUS;
    }

    /**
     * @return bool
     */
    public function isUnread()
    {
        return $this->status === self::UNREAD_STATUS;
    }

    /**
     * @return null
     */
    public function getStatusLabel()
    {
        $statusList = self::getStatusLabels();

        if (array_key_exists($this->status, $statusList)) {
            return $statusList[$this->status];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getFullLengthOccupation()
    {
        return sprintf(
            '%s %s',
            $this->lengthOccupation,
            $this->lengthTypeOccupation
        );
    }

    /**
     * @param User $user
     *
     * @return Application
     */
    public static function createFromUser(User $user)
    {
        $application = new Application();

        $application->setDescription($user->getProjectDescription());
        $application->setLengthOccupation($user->getUsageDuration());
        $application->setLengthTypeOccupation($user->getLengthTypeOccupation());
        $application->setWishedSize($user->getWishedSize());
        $application->setProjectHolder($user);

        return $application;
    }

    /**
     * @param ExecutionContextInterface $context
     */
    #[Assert\Callback]
    public function validateContribution(ExecutionContextInterface $context)
    {
        $contribution = $this->contribution;
        if ($this->openToGlobalProject && empty($contribution)) {
            $context->buildViolation('Cette valeur ne doit pas être vide')
                    ->atPath('contribution')
                    ->addViolation();
        }
    }

    /**
     * Set selected
     *
     * @param boolean $selected
     * @return Application
     */
    public function setSelected($selected)
    {
        $this->selected = $selected;

        return $this;
    }

    /**
     * Get selected
     *
     * @return boolean
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * @param ExecutionContextInterface $context
     */
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context)
    {
        $mimes = [
            "image/png",
            "image/jpeg",
            "image/jpg",
            "image/gif",
            "image/webp",
            "application/pdf",
            "application/x-pdf",
            "application/msword"
        ];

        $constraints = [
            new Assert\File([
                'maxSize' => "10M",
                'mimeTypes' => $mimes
            ])
        ];

        foreach($this->getFiles() as $applicationfile) {
            $path = ($applicationfile->getSpaceDocument()) ? '_'.$applicationfile->getSpaceDocument()->getName() : 'newDocument';

            $context->getValidator()
                    ->inContext($context)
                    ->atPath($path)
                    ->validate($applicationfile->getFile(), $constraints);
        }
    }
}
