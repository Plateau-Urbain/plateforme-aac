<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * User.
 */
#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Vous pouvez réinitialiser votre mot de passe depuis la page connexion.')]
#[ORM\Table(name: 'fos_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, LegacyPasswordAuthenticatedUserInterface, \Stringable
{
    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: true)]
    protected $username;

    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: true)]
    protected $usernameCanonical;

    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: true)]
    protected $emailCanonical;

    #[ORM\Column(type: 'boolean')]
    protected $enabled = true;

    protected $locked = false;

    #[ORM\Column(type: 'string')]
    protected $password;

    protected ?string $plainPassword = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected $salt;

    #[ORM\Column(type: 'json')]
    protected $roles = [];

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'confirmation_token', type: 'string', length: 180, nullable: true)]
    protected ?string $confirmationToken = null;

    #[ORM\Column(name: 'password_requested_at', type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $passwordRequestedAt = null;

    const PORTEUR = 0;
    const PROPRIO = 1;
    const ADMIN = 2;

    const MISTER = 'M';
    const MISS = 'Mme';
    const AUTRE = 'Atr';

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\Column(name: 'facebook_id', type: 'string', length: 255, nullable: true)]
    protected $facebookId;

    #[ORM\Column(name: 'google_id', type: 'string', length: 255, nullable: true)]
    protected $googleId;

    #[ORM\Column(name: 'linkedin_id', type: 'string', length: 255, nullable: true)]
    protected $linkedinId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'civility', length: 3, type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez sélectionner votre civilité.", groups: ['projectHolder', 'owner'])]
    protected $civility;

    #[ORM\Column(name: 'firstname', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner votre prénom.", groups: ['projectHolder', 'owner'])]
    protected $firstname;

    #[ORM\Column(name: 'lastname', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner votre nom.", groups: ['projectHolder', 'owner'])]
    protected $lastname;

    #[ORM\Column(name: 'email', type: 'string', length: 180, unique: true, nullable: true)]
    #[Assert\Email(groups: ['projectHolder', 'owner'])]
    #[Assert\NotBlank(message: "Veuillez renseigner votre adresse email.", groups: ['projectHolder', 'owner'])]
    protected $email;

    /**
     * @var string
     */
    #[ORM\Column(name: 'newsletter', type: 'boolean', nullable: true)]
    protected $newsletter;

    /**
     * @var
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\Group::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'users_groups')]
    protected $groups;

    #[ORM\Column(length: 255, type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner le nom de votre structure.", groups: ['projectHolder', 'owner'])]
    protected $company;

    #[ORM\Column(name: 'company_status', length: 255, type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner le statut juridique de votre structure.", groups: ['projectHolder', 'owner'])]
    protected $companyStatus;

    #[ORM\Column(name: 'company_creation_date', type: 'date', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner la date de création de votre structure.", groups: ['projectHolder'])]
    protected $companyCreationDate;

    #[ORM\Column(length: 255, type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner votre adresse.", groups: ['projectHolder', 'owner'])]
    protected $address;

    #[ORM\Column(name: 'address_suite', length: 255, type: 'string', nullable: true)]
    protected $addressSuite;

    #[ORM\Column(name: 'phone', length: 255, type: 'string', nullable: true)]
    protected $phone;

    #[ORM\Column(name: 'company_phone', length: 255, type: 'string', nullable: true)]
    protected $companyPhone;

    #[ORM\Column(name: 'company_mobile', length: 255, type: 'string', nullable: true)]
    protected $companyMobile;

    #[ORM\Column(name: 'company_site', length: 255, type: 'string', nullable: true)]
    protected $company_site;

    #[ORM\Column(name: 'company_blog', length: 255, type: 'string', nullable: true)]
    protected $company_blog;

    #[ORM\Column(length: 10, type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner votre code postal.", groups: ['projectHolder', 'owner'])]
    #[Assert\Length(max: 5, min: 5, minMessage: 'Code postal invalide', maxMessage: 'Code postal invalide', groups: ['projectHolder', 'owner'])]
    #[Assert\Regex(pattern: '/[0-9]{2}[0-9]{3}/', message: 'Code postal invalide', groups: ['projectHolder', 'owner'])]
    protected $zipcode;

    #[ORM\Column(length: 255, type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner votre ville.", groups: ['projectHolder', 'owner'])]
    protected $city;

    #[ORM\Column(name: 'company_function', length: 255, type: 'string', nullable: true)]
    protected $companyFunction;

    #[ORM\Column(name: 'company_description', type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'Veuillez renseigner la présentation de votre structure.', groups: ['projectHolder'])]
    protected $companyDescription;

    #[ORM\Column(name: 'company_effective', type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: 'Veuillez renseigner le nombre de personnes dans votre structure.', groups: ['projectHolder'])]
    #[Assert\Range(min: 0, minMessage: 'Vous devez obligatoirement renseigner une valeur positive.', groups: ['projectHolder'])]
    protected $companyEffective;

    #[ORM\Column(name: 'company_structures', length: 255, type: 'string', nullable: true)]
    protected $companyStructures;

    /**
     * @var int
     *          0 : porteur de projet
     *          1 : proprio
     *          2 : admin
     */
    #[ORM\Column(name: 'typeUser', type: 'integer', nullable: true)]
    private $typeUser;

    /**PROJECT HOLDER FIELD  **/
    /**
     * @var \Date
     */
    #[ORM\Column(name: 'birthday', type: 'date', nullable: true)]
    #[Assert\NotBlank(groups: ['projectHolder'])]
    private $birthday;

    #[ORM\Column(name: 'facebookUrl', length: 255, type: 'string', nullable: true)]
    protected $facebookUrl;

    #[ORM\Column(name: 'instagramUrl', length: 255, type: 'string', nullable: true)]
    protected $instagramUrl;

    #[ORM\Column(name: 'twitterUrl', length: 255, type: 'string', nullable: true)]
    protected $twitterUrl;

    #[ORM\Column(name: 'google_url', length: 255, type: 'string', nullable: true)]
    protected $googleUrl;

    #[ORM\Column(name: 'linkedin_url', length: 255, type: 'string', nullable: true)]
    protected $linkedinUrl;

    #[ORM\Column(name: 'other_url', length: 255, type: 'string', nullable: true)]
    protected $otherUrl;

    #[ORM\Column(name: 'youtube_url', length: 255, type: 'string', nullable: true)]
    protected $youtubeUrl;

    #[ORM\Column(name: 'tiktok_url', length: 255, type: 'string', nullable: true)]
    protected $tiktokUrl;

    #[ORM\Column(type: 'text', nullable: true)]
    protected $description;

    #[ORM\Column(length: 255, type: 'string', nullable: true)]
    protected $siret;

    #[ORM\Column(name: 'wishedSize', type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner la surface souhaitée.", groups: ['projectHolder'])]
    #[Assert\Type(type: 'integer', message: "La valeur {{ value }} n'est pas un nombre entier valide.", groups: ['projectHolder', 'register'])]
    #[Assert\Range(min: 0, minMessage: 'Vous devez obligatoirement renseigner une surface positive.', groups: ['projectHolder', 'register'])]
    protected $wishedSize;

    #[ORM\Column(name: 'usage_date', type: 'date', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner votre date de disponibilité.", groups: ['projectHolder'])]
    protected $usageDate;

    /**
     * @var string
     */
    #[ORM\Column(name: 'length_type_occupation', type: 'string', length: 15, nullable: true)]
    private $lengthTypeOccupation;

    #[ORM\Column(name: 'usageDuration', type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner la durée d'occupation souhaitée.", groups: ['projectHolder'])]
    #[Assert\Type(type: 'integer', message: "La valeur {{ value }} n'est pas un nombre entier valide.", groups: ['projectHolder'])]
    #[Assert\Range(min: 0, minMessage: 'Vous devez obligatoirement renseigner une durée positive.', groups: ['projectHolder'])]
    protected $usageDuration;

    /**
     * Budget mensuel total maximum (en euros).
     */
    #[ORM\Column(name: 'monthly_budget_max', type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner votre budget mensuel maximum.", groups: ['projectHolder'])]
    #[Assert\Type(type: 'integer', message: "La valeur {{ value }} n'est pas un nombre entier valide.", groups: ['projectHolder'])]
    #[Assert\Range(min: 0, minMessage: 'Vous devez renseigner un budget positif.', groups: ['projectHolder'])]
    protected $monthlyBudgetMax;

    #[ORM\Column(name: 'project_description', type: 'text', nullable: true)]
    protected $projectDescription;

    /**
     * Quel sera l'usage du local ?
     */
    #[ORM\Column(name: 'local_usage_description', type: 'text', nullable: true)]
    protected $localUsageDescription;

    #[ORM\Column(name: 'preferred_departments', type: 'simple_array', nullable: true)]
    #[Assert\Count(min: 1, minMessage: 'Veuillez sélectionner au moins un département.', groups: ['projectHolder'])]
    protected $preferredDepartments = [];

    #[ORM\ManyToOne(targetEntity: \App\Entity\UseType::class)]
    #[ORM\JoinColumn(name: 'useType_id', referencedColumnName: 'id', nullable: true)]
    #[Assert\NotBlank(groups: ['projectHolder'])]
    protected $useType;

    #[ORM\OneToMany(targetEntity: \App\Entity\Application::class, mappedBy: 'projectHolder', cascade: ['remove'])]
    protected $applications;

    #[ORM\OneToMany(targetEntity: \App\Entity\UserDocument::class, mappedBy: 'projectHolder', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    protected $documents;

    #[ORM\OneToMany(targetEntity: \App\Entity\Space::class, mappedBy: 'owner', cascade: ['remove'])]
    protected $spaces;

    public function __toString(): string
    {
        return $this->getFirstname() . ' ' . $this->getLastname() . ' - ' . $this->getCompany();
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
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
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return mixed
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * @param mixed $zipcode
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        $this->setUsername($email);
    }

    public function setEmailCanonical($emailCanonical)
    {
        $this->emailCanonical = $emailCanonical;
        $this->setUsernameCanonical($emailCanonical);
    }

    /**
     * Set Type User.
     *
     * @param int $typeUser
     *
     * @return User
     */
    public function setTypeUser($typeUser)
    {
        $this->typeUser = $typeUser;

        return $this;
    }

    /**
     * Get Type User.
     *
     * @return int
     */
    public function getTypeUser()
    {
        return $this->typeUser;
    }

    /**
     * Is proprio.
     *
     * @return int
     */
    public function isProprio()
    {
        return $this->typeUser == self::PROPRIO;
    }

    /**
     * is porteur.
     *
     * @return int
     */
    public function isPorteur()
    {
        return $this->typeUser == self::PORTEUR;
    }

    /**
     * @return mixed
     */
    public function getInstagramUrl()
    {
        return $this->instagramUrl;
    }

    /**
     * @param mixed $instagramUrl
     */
    public function setInstagramUrl($instagramUrl)
    {
        $this->instagramUrl = $instagramUrl;
    }

    /**
     * @return mixed
     */
    public function getSiret()
    {
        return $this->siret;
    }

    /**
     * @param mixed $siret
     */
    public function setSiret($siret)
    {
        $this->siret = $siret;
    }

    /**
     * @return mixed
     */
    public function getUsageDate()
    {
        return $this->usageDate;
    }

    /**
     * @param mixed $usageDate
     */
    public function setUsageDate($usageDate)
    {
        $this->usageDate = $usageDate;
    }

    /**
     * @return string|null
     */
    public function getUsageDuration(): ?string
    {
        return $this->usageDuration;
    }

    /**
     * @param mixed $usageDuration
     */
    public function setUsageDuration($usageDuration)
    {
        $this->usageDuration = $usageDuration;
    }

    /**
     * @return int|null
     */
    public function getMonthlyBudgetMax()
    {
        return $this->monthlyBudgetMax;
    }

    /**
     * @param int|null $monthlyBudgetMax
     */
    public function setMonthlyBudgetMax($monthlyBudgetMax)
    {
        $this->monthlyBudgetMax = $monthlyBudgetMax;
    }

    /**
     * @return mixed
     */
    public function getUsageType()
    {
        return $this->usageType;
    }

    /**
     * @param mixed $usageType
     */
    public function setUsageType($usageType)
    {
        $this->usageType = $usageType;
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
    public function getFacebookUrl()
    {
        return $this->facebookUrl;
    }

    /**
     * @param mixed $facebookUrl
     */
    public function setFacebookUrl($facebookUrl)
    {
        $this->facebookUrl = $facebookUrl;
    }

    /**
     * @return mixed
     */
    public function getTwitterUrl()
    {
        return $this->twitterUrl;
    }

    /**
     * @param mixed $twitterUrl
     */
    public function setTwitterUrl($twitterUrl)
    {
        $this->twitterUrl = $twitterUrl;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }


    /**
     * @return \Date
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param \Date $birthday
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    }

    /**
     * @return mixed
     */
    public function getCivility()
    {
        return $this->civility;
    }

    /**
     * @param mixed $civility
     */
    public function setCivility($civility)
    {
        $this->civility = $civility;
    }

    /**
     * @return mixed
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * @param mixed $newsletter
     */
    public function setNewsletter($newsletter)
    {
        $this->newsletter = $newsletter;
    }

    /**
     * @return mixed
     */
    public function getUseType()
    {
        return $this->useType;
    }

    /**
     * @param mixed $useType
     */
    public function setUseType($useType)
    {
        $this->useType = $useType;
    }

    /**
     * @return array
     */
    public function getPreferredDepartments()
    {
        return $this->preferredDepartments ?: [];
    }

    /**
     * @param array|null $preferredDepartments
     */
    public function setPreferredDepartments($preferredDepartments)
    {
        if ($preferredDepartments === null) {
            $this->preferredDepartments = [];
            return;
        }

        $this->preferredDepartments = array_values(array_unique(array_map(strval(...), (array) $preferredDepartments)));
    }

    /**
     * Libellés des départements souhaités (pour récapitulatifs, exports).
     *
     * @return string[]
     */
    public function getPreferredDepartmentsLabels()
    {
        $byCode = [];
        foreach (self::getAllFrenchDepartments() as $label => $code) {
            $byCode[(string) $code] = $label;
        }

        $labels = [];
        foreach ($this->getPreferredDepartments() as $code) {
            $code = (string) $code;
            $labels[] = $byCode[$code] ?? $code;
        }

        return $labels;
    }

    /**
     * Texte pour exports CSV (libellés séparés par « ; »).
     *
     * @return string
     */
    public function getPreferredDepartmentsLabelsForExport()
    {
        $labels = $this->getPreferredDepartmentsLabels();

        return implode('; ', $labels);
    }

    /**
     * @return array
     */
    public static function getAllCivilities() {
        return [
            self::MISTER => self::MISTER,
            self::MISS => self::MISS,
            self::AUTRE => 'Autre'
        ];
    }

    /**
     * @return array
     */
    public static function getAllFrenchDepartments()
    {
        return [
            '01 - Ain' => '01',
            '02 - Aisne' => '02',
            '03 - Allier' => '03',
            '04 - Alpes-de-Haute-Provence' => '04',
            '05 - Hautes-Alpes' => '05',
            '06 - Alpes-Maritimes' => '06',
            '07 - Ardeche' => '07',
            '08 - Ardennes' => '08',
            '09 - Ariege' => '09',
            '10 - Aube' => '10',
            '11 - Aude' => '11',
            '12 - Aveyron' => '12',
            '13 - Bouches-du-Rhone' => '13',
            '14 - Calvados' => '14',
            '15 - Cantal' => '15',
            '16 - Charente' => '16',
            '17 - Charente-Maritime' => '17',
            '18 - Cher' => '18',
            '19 - Correze' => '19',
            '2A - Corse-du-Sud' => '2A',
            '2B - Haute-Corse' => '2B',
            '21 - Cote-d\'Or' => '21',
            '22 - Cotes-d\'Armor' => '22',
            '23 - Creuse' => '23',
            '24 - Dordogne' => '24',
            '25 - Doubs' => '25',
            '26 - Drome' => '26',
            '27 - Eure' => '27',
            '28 - Eure-et-Loir' => '28',
            '29 - Finistere' => '29',
            '30 - Gard' => '30',
            '31 - Haute-Garonne' => '31',
            '32 - Gers' => '32',
            '33 - Gironde' => '33',
            '34 - Herault' => '34',
            '35 - Ille-et-Vilaine' => '35',
            '36 - Indre' => '36',
            '37 - Indre-et-Loire' => '37',
            '38 - Isere' => '38',
            '39 - Jura' => '39',
            '40 - Landes' => '40',
            '41 - Loir-et-Cher' => '41',
            '42 - Loire' => '42',
            '43 - Haute-Loire' => '43',
            '44 - Loire-Atlantique' => '44',
            '45 - Loiret' => '45',
            '46 - Lot' => '46',
            '47 - Lot-et-Garonne' => '47',
            '48 - Lozere' => '48',
            '49 - Maine-et-Loire' => '49',
            '50 - Manche' => '50',
            '51 - Marne' => '51',
            '52 - Haute-Marne' => '52',
            '53 - Mayenne' => '53',
            '54 - Meurthe-et-Moselle' => '54',
            '55 - Meuse' => '55',
            '56 - Morbihan' => '56',
            '57 - Moselle' => '57',
            '58 - Nievre' => '58',
            '59 - Nord' => '59',
            '60 - Oise' => '60',
            '61 - Orne' => '61',
            '62 - Pas-de-Calais' => '62',
            '63 - Puy-de-Dome' => '63',
            '64 - Pyrenees-Atlantiques' => '64',
            '65 - Hautes-Pyrenees' => '65',
            '66 - Pyrenees-Orientales' => '66',
            '67 - Bas-Rhin' => '67',
            '68 - Haut-Rhin' => '68',
            '69 - Rhone' => '69',
            '70 - Haute-Saone' => '70',
            '71 - Saone-et-Loire' => '71',
            '72 - Sarthe' => '72',
            '73 - Savoie' => '73',
            '74 - Haute-Savoie' => '74',
            '75 - Paris' => '75',
            '76 - Seine-Maritime' => '76',
            '77 - Seine-et-Marne' => '77',
            '78 - Yvelines' => '78',
            '79 - Deux-Sevres' => '79',
            '80 - Somme' => '80',
            '81 - Tarn' => '81',
            '82 - Tarn-et-Garonne' => '82',
            '83 - Var' => '83',
            '84 - Vaucluse' => '84',
            '85 - Vendee' => '85',
            '86 - Vienne' => '86',
            '87 - Haute-Vienne' => '87',
            '88 - Vosges' => '88',
            '89 - Yonne' => '89',
            '90 - Territoire de Belfort' => '90',
            '91 - Essonne' => '91',
            '92 - Hauts-de-Seine' => '92',
            '93 - Seine-Saint-Denis' => '93',
            '94 - Val-de-Marne' => '94',
            '95 - Val-d\'Oise' => '95',
            '971 - Guadeloupe' => '971',
            '972 - Martinique' => '972',
            '973 - Guyane' => '973',
            '974 - La Reunion' => '974',
            '976 - Mayotte' => '976'
        ];
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->typeUser = self::PORTEUR;
        $this->preferredDepartments = [];
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
     * Set companyStatus
     *
     * @param string $companyStatus
     * @return User
     */
    public function setCompanyStatus($companyStatus)
    {
        $this->companyStatus = $companyStatus;

        return $this;
    }

    /**
     * Get companyStatus
     *
     * @return string
     */
    public function getCompanyStatus()
    {
        return $this->companyStatus;
    }

    /**
     * Set companyCreationDate
     *
     * @param \DateTime $companyCreationDate
     * @return User
     */
    public function setCompanyCreationDate($companyCreationDate)
    {
        $this->companyCreationDate = $companyCreationDate;

        return $this;
    }

    /**
     * Get companyCreationDate
     *
     * @return \DateTime
     */
    public function getCompanyCreationDate()
    {
        return $this->companyCreationDate;
    }

    /**
     * Set addressSuite
     *
     * @param string $addressSuite
     * @return User
     */
    public function setAddressSuite($addressSuite)
    {
        $this->addressSuite = $addressSuite;

        return $this;
    }

    /**
     * Get addressSuite
     *
     * @return string
     */
    public function getAddressSuite()
    {
        return $this->addressSuite;
    }

    /**
     * Set companyPhone
     *
     * @param string $companyPhone
     * @return User
     */
    public function setCompanyPhone($companyPhone)
    {
        $this->companyPhone = $companyPhone;

        return $this;
    }

    /**
     * Get companyPhone
     *
     * @return string
     */
    public function getCompanyPhone()
    {
        return $this->companyPhone;
    }

    /**
     * Set companyMobile
     *
     * @param string $companyMobile
     * @return User
     */
    public function setCompanyMobile($companyMobile)
    {
        $this->companyMobile = $companyMobile;

        return $this;
    }

    /**
     * Get companyMobile
     *
     * @return string
     */
    public function getCompanyMobile()
    {
        return $this->companyMobile;
    }

    /**
     * Set company_site
     *
     * @param string $companySite
     * @return User
     */
    public function setCompanySite($companySite)
    {
        $this->company_site = $companySite;

        return $this;
    }

    /**
     * Get company_site
     *
     * @return string
     */
    public function getCompanySite(): ?string
    {
        return $this->company_site;
    }

    /** Alias pour la compatibilité des templates (space.owner.website). */
    public function getWebsite(): ?string
    {
        return $this->company_site;
    }

    /**
     * Set company_blog
     *
     * @param string $companyBlog
     * @return User
     */
    public function setCompanyBlog($companyBlog)
    {
        $this->company_blog = $companyBlog;

        return $this;
    }

    /**
     * Get company_blog
     *
     * @return string
     */
    public function getCompanyBlog()
    {
        return $this->company_blog;
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Set googleUrl
     *
     * @param string $googleUrl
     * @return User
     */
    public function setGoogleUrl($googleUrl)
    {
        $this->googleUrl = $googleUrl;

        return $this;
    }

    /**
     * Get googleUrl
     *
     * @return string
     */
    public function getGoogleUrl()
    {
        return $this->googleUrl;
    }

    /**
     * Set linkedinUrl
     *
     * @param string $linkedinUrl
     * @return User
     */
    public function setLinkedinUrl($linkedinUrl)
    {
        $this->linkedinUrl = $linkedinUrl;

        return $this;
    }

    /**
     * Get linkedinUrl
     *
     * @return string
     */
    public function getLinkedinUrl()
    {
        return $this->linkedinUrl;
    }

    /**
     * Set otherUrl
     *
     * @param string $otherUrl
     * @return User
     */
    public function setOtherUrl($otherUrl)
    {
        $this->otherUrl = $otherUrl;

        return $this;
    }

    /**
     * Get otherUrl
     *
     * @return string
     */
    public function getOtherUrl()
    {
        return $this->otherUrl;
    }

    /**
     * @param string $youtubeUrl
     * @return User
     */
    public function setYoutubeUrl($youtubeUrl)
    {
        $this->youtubeUrl = $youtubeUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getYoutubeUrl()
    {
        return $this->youtubeUrl;
    }

    /**
     * @param string $tiktokUrl
     * @return User
     */
    public function setTiktokUrl($tiktokUrl)
    {
        $this->tiktokUrl = $tiktokUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getTiktokUrl()
    {
        return $this->tiktokUrl;
    }

    /**
     * Set lengthTypeOccupation
     *
     * @param string $lengthTypeOccupation
     * @return User
     */
    public function setLengthTypeOccupation($lengthTypeOccupation)
    {
        $this->lengthTypeOccupation = $lengthTypeOccupation;

        return $this;
    }

    /**
     * Get lengthTypeOccupation
     *
     * @return string
     */
    public function getLengthTypeOccupation()
    {
        return $this->lengthTypeOccupation;
    }

    /**
     * Add groups
     *
     * @param \App\Entity\Group $groups
     * @return User
     */
    public function addGroup(\App\Entity\Group $groups): self
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     */
    public function removeGroup(\App\Entity\Group $groups): void
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Set projectDescription
     *
     * @param string $projectDescription
     * @return User
     */
    public function setProjectDescription($projectDescription)
    {
        $this->projectDescription = $projectDescription;

        return $this;
    }

    /**
     * Get projectDescription
     *
     * @return string
     */
    public function getProjectDescription()
    {
        return $this->projectDescription;
    }

    /**
     * @param string|null $localUsageDescription
     * @return User
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
     * Set companyDescription
     *
     * @param string $companyDescription
     * @return User
     */
    public function setCompanyDescription($companyDescription)
    {
        $this->companyDescription = $companyDescription;

        return $this;
    }

    /**
     * Get companyDescription
     *
     * @return string
     */
    public function getCompanyDescription()
    {
        return $this->companyDescription;
    }

    /**
     * Set companyDescription
     *
     * @param string $companyFunction
     * @return User
     */
    public function setCompanyFunction($companyFunction)
    {
        $this->companyFunction = $companyFunction;

        return $this;
    }

    /**
     * Get companyDescription
     *
     * @return string
     */
    public function getCompanyFunction()
    {
        return $this->companyFunction;
    }

    /**
     * Set companyEffective
     *
     * @param integer $companyEffective
     * @return User
     */
    public function setCompanyEffective($companyEffective)
    {
        $this->companyEffective = $companyEffective;

        return $this;
    }

    /**
     * Get companyEffective
     *
     * @return integer
     */
    public function getCompanyEffective()
    {
        return $this->companyEffective;
    }

    /**
     * Set companyStructures
     *
     * @param string $companyStructures
     * @return User
     */
    public function setCompanyStructures($companyStructures)
    {
        $this->companyStructures = $companyStructures;

        return $this;
    }

    /**
     * Get companyStructures
     *
     * @return string
     */
    public function getCompanyStructures()
    {
        return $this->companyStructures;
    }

    /**
     * Get all company statut
     *
     * @return array
     */
    public static function getAllCompanyStatut() {
        return [
            "Association"                           => "Association",
            "Artiste"                               => "Artiste",
            "ESS"                                   => "ESS",
            "Autoentrepreneur"                      => "Autoentrepreneur",
            "Profession libérale"                   => "Profession libérale",
            "En création"                           => "En création",
            "En phase de lancement de moins 2 ans"  => "En phase de lancement de moins 2 ans"
            ];
    }

    /**
     * Get all company statut for pro
     *
     * @return array
     */
    public static function getAllProCompanyStatut() {
        return [
            // Listes détaillées (choix individuels) + options "groupées" demandées.
            // Clés = valeurs (stockage en base en clair).
            'Association loi 1901' => 'Association loi 1901',
            'Micro Entreprise (Auto Entrepreneur)' => 'Micro Entreprise (Auto Entrepreneur)',
            
            'Profession libérale' => 'Profession libérale',
            'Affiliée à la Maison des Artistes' => 'Affiliée à la Maison des Artistes',
            'Etablissement public' => 'Etablissement public',
            'Statut en cours de création' => 'Statut en cours de création',

            // Autres statuts existants (conservés)
            'SA' => 'SA',
            'SAS' => 'SAS',
            'SARL' => 'SARL',
            
            'SNC' => 'SNC',
            'EURL' => 'EURL',
            'SCIC' => 'SCIC',
            'SCOP' => 'SCOP',
            'CAE' => 'CAE'
            
        ];
    }

    /**
     * Indique si la valeur enregistrée figure dans le catalogue actuel des statuts
     * proposés dans les formulaires (profil / structure).
     *
     * @param string|null $status
     * @return bool
     */
    public static function isInProCompanyStatusCatalog($status)
    {
        if ($status === null || $status === '') {
            return true;
        }
        foreach (self::getAllProCompanyStatut() as $value) {
            if ($value === $status) {
                return true;
            }
        }

        return false;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string
     */
    public function getFullname() {
        return sprintf(
            "%s %s %s",
            $this->getCivility(),
            $this->getFirstname(),
            $this->getLastname()
        );
    }

    /**
     * Set facebookId
     *
     * @param string $facebookId
     * @return User
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    /**
     * Get facebookId
     *
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }

    /**
     * Add applications
     *
     * @param \App\Entity\Application $applications
     * @return User
     */
    public function addApplication(\App\Entity\Application $applications)
    {
        $this->applications[] = $applications;

        return $this;
    }

    /**
     * Remove applications
     *
     * @param \App\Entity\Application $applications
     */
    public function removeApplication(\App\Entity\Application $applications)
    {
        $this->applications->removeElement($applications);
    }

    /**
     * Get applications
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getApplications()
    {
        return $this->applications;
    }

    /**
     * Add spaces
     *
     * @param \App\Entity\Space $spaces
     * @return User
     */
    public function addSpace(\App\Entity\Space $spaces)
    {
        $this->spaces[] = $spaces;

        return $this;
    }

    /**
     * Add documents
     *
     * @param \App\Entity\UserDocument $documents
     * @return User
     */
    public function addDocument(\App\Entity\UserDocument $documents)
    {
        $this->documents[] = $documents;

        return $this;
    }

    /**
     * Remove spaces
     *
     * @param \App\Entity\Space $spaces
     */
    public function removeSpace(\App\Entity\Space $spaces)
    {
        $this->spaces->removeElement($spaces);
    }

    /**
     * Get spaces
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSpaces()
    {
        return $this->spaces;
    }

    /**
     * Set googleId
     *
     * @param string $googleId
     * @return User
     */
    public function setGoogleId($googleId)
    {
        $this->googleId = $googleId;

        return $this;
    }

    /**
     * Get googleId
     *
     * @return string
     */
    public function getGoogleId()
    {
        return $this->googleId;
    }

    /**
     * Set linkedinId
     *
     * @param string $linkedinId
     * @return User
     */
    public function setLinkedinId($linkedinId)
    {
        $this->linkedinId = $linkedinId;

        return $this;
    }

    /**
     * Remove documents
     *
     * @param \App\Entity\UserDocument $documents
     */
    public function removeDocument(\App\Entity\UserDocument $documents)
    {
        $this->documents->removeElement($documents);
    }

    /**
     * Has document type
     *
     * @return mixed
     */
    public function hasDocuments($type)
    {
      foreach ($this->documents as $document) {
        if ($document->getType() == $type){
          return true;
        }
      }

      return false;
    }

    /**
     * Has document type
     *
     * @return \App\Entity\UserDocument[]
     */
    public function getDocumentsType(string $type): array
    {
      $documents = [];

      foreach ($this->documents as $document) {
        if ($document->getType() == $type){
          $documents[] = $document;
        }
      }
      return $documents;
    }

    /**
     * Get documents
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\UserDocument>
     */
    public function getDocuments(): \Doctrine\Common\Collections\Collection
    {
        return $this->documents;
    }

    /**
     * Get linkedinId
     *
     * @return string
     */
    public function getLinkedinId()
    {
        return $this->linkedinId;
    }

    /**
     * Vérifie si le profil utilisateur est complet pour pouvoir candidater
     * 
     * @return bool
     */
    public function isProfileComplete()
    {
        // Vérifier les champs obligatoires de base
        if (empty($this->civility) || empty($this->firstname) || empty($this->lastname) || empty($this->email)) {
            return false;
        }

        // Vérifier les informations de l'entreprise
        if (empty($this->company) || empty($this->companyStatus) || empty($this->address) || 
            empty($this->zipcode) || empty($this->city)) {
            return false;
        }

        // Vérifier les informations spécifiques au porteur de projet
        if ($this->isPorteur()) {
            if (empty($this->birthday) || empty($this->companyCreationDate) || 
                empty($this->wishedSize) || empty($this->useType) || empty($this->usageDate) || empty($this->usageDuration) ||
                empty($this->preferredDepartments) || empty($this->monthlyBudgetMax)) {
                return false;
            }
        }

        // Vérifier les documents obligatoires
        if (!$this->hasDocuments(UserDocument::ID_TYPE) || !$this->hasDocuments(UserDocument::KBIS_TYPE)) {
            return false;
        }

        return true;
    }

    /**
     * Retourne la liste des champs manquants pour compléter le profil
     * 
     * @return array
     */
    public function getMissingProfileFields()
    {
        $missing = [];

        if (empty($this->civility)) {
            $missing[] = 'Civilité';
        }
        if (empty($this->firstname)) {
            $missing[] = 'Prénom';
        }
        if (empty($this->lastname)) {
            $missing[] = 'Nom';
        }
        if (empty($this->email)) {
            $missing[] = 'Email';
        }
        if (empty($this->company)) {
            $missing[] = 'Nom de l\'entreprise';
        }
        if (empty($this->companyStatus)) {
            $missing[] = 'Statut de l\'entreprise';
        }
        if (empty($this->address)) {
            $missing[] = 'Adresse';
        }
        if (empty($this->zipcode)) {
            $missing[] = 'Code postal';
        }
        if (empty($this->city)) {
            $missing[] = 'Ville';
        }

        if ($this->isPorteur()) {
            if (empty($this->birthday)) {
                $missing[] = 'Date de naissance';
            }
            if (empty($this->companyCreationDate)) {
                $missing[] = 'Date de création de l\'entreprise';
            }
            if (empty($this->wishedSize)) {
                $missing[] = 'Surface souhaitée';
            }
            if (empty($this->monthlyBudgetMax)) {
                $missing[] = 'Budget mensuel total maximum';
            }
            if (empty($this->useType)) {
                $missing[] = 'Type d\'usage';
            }
            if (empty($this->usageDate)) {
                $missing[] = 'Date de disponibilité';
            }
            if (empty($this->usageDuration)) {
                $missing[] = 'Durée d\'occupation';
            }
            if (empty($this->preferredDepartments)) {
                $missing[] = 'Départements souhaités';
            }
        }

        if (!$this->hasDocuments(UserDocument::ID_TYPE)) {
            $missing[] = 'Pièce d\'identité';
        }
        if (!$this->hasDocuments(UserDocument::KBIS_TYPE)) {
            $missing[] = 'Kbis ou document équivalent';
        }

        return $missing;
    }

    /**
     * @param ExecutionContextInterface $context
     */
    #[Assert\Callback]
    public function validateDocuments(ExecutionContextInterface $context)
    {
        $mimes = [
            "image/png",
            "image/jpeg",
            "image/jpg",
            "application/pdf",
            "application/x-pdf",
            "application/msword"
        ];

        foreach ($this->getDocuments() as $doc) {
            if (! $doc->getType()) {
                continue;
            }

            $path = $doc->getType() === 'kbis' ? 'kbis' : 'idcard';

            $constraints = [
                new Assert\File([
                    'maxSize' => "10M",
                    'mimeTypes' => $mimes
                ])
            ];

            $context->getValidator()
                    ->inContext($context)
                    ->atPath($path)
                    ->validate($doc->getFile(), $constraints);
        }
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        $this->usernameCanonical = $username ? strtolower($username) : null;
        return $this;
    }

    public function getUsernameCanonical(): ?string
    {
        return $this->usernameCanonical;
    }

    public function setUsernameCanonical(?string $usernameCanonical): self
    {
        $this->usernameCanonical = $usernameCanonical;
        return $this;
    }

    public function getEmailCanonical(): ?string
    {
        return $this->emailCanonical;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(?string $salt): self
    {
        $this->salt = $salt;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // Compat SF3 -> SF6: certains comptes historiques "admin" reposaient
        // sur typeUser sans ROLE_ADMIN explicite en base.
        if ($this->typeUser === self::ADMIN) {
            $roles[] = 'ROLE_ADMIN';
        }

        // Compat SF3 -> SF6: les propriétaires historiques reposaient souvent
        // sur typeUser sans ROLE_OWNER explicite en base.
        if ($this->typeUser === self::PROPRIO) {
            $roles[] = 'ROLE_OWNER';
        }

        // assure au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getPasswordRequestedAt(): ?\DateTimeInterface
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(?\DateTimeInterface $passwordRequestedAt): self
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    public function addRole(string $role): self
    {
        $role = strtoupper(trim($role));
        if ($role === '') {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        $role = strtoupper(trim($role));
        if ($role === '') {
            return $this;
        }

        $this->roles = array_values(array_filter(
            $this->roles,
            static fn (string $existingRole): bool => $existingRole !== $role
        ));

        return $this;
    }

    public function hasRole(string $role): bool
    {
        $role = strtoupper(trim($role));
        if ($role === '') {
            return false;
        }

        return in_array($role, $this->getRoles(), true);
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isLocked(): bool
    {
        return (bool) $this->locked;
    }

    public function getLocked(): bool
    {
        return $this->isLocked();
    }

    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;
        return $this;
    }

    public function __serialize(): array
    {
        return [
            'id'       => $this->id,
            'email'    => $this->email,
            'password' => $this->password,
            'salt'     => $this->salt,
            'roles'    => $this->roles,
            'enabled'  => $this->enabled,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id       = $data['id']       ?? null;
        $this->email    = $data['email']    ?? null;
        $this->password = $data['password'] ?? null;
        $this->salt     = $data['salt']     ?? null;
        $this->roles    = $data['roles']    ?? [];
        $this->enabled  = $data['enabled']  ?? true;
    }
}
