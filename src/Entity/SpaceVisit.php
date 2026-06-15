<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'space_visit')]
class SpaceVisit
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'visit_date', type: 'date')]
    #[Assert\NotBlank]
    private $visitDate;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'start_time', type: 'time')]
    #[Assert\NotBlank]
    private $startTime;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'end_time', type: 'time')]
    #[Assert\NotBlank]
    private $endTime;

    #[ORM\ManyToOne(targetEntity: \App\Entity\Space::class, inversedBy: 'visits')]
    #[ORM\JoinColumn(name: 'space_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $space;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $visitDate
     * @return $this
     */
    public function setVisitDate($visitDate)
    {
        $this->visitDate = $visitDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getVisitDate()
    {
        return $this->visitDate;
    }

    /**
     * @param \DateTime $startTime
     * @return $this
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param \DateTime $endTime
     * @return $this
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param Space $space
     * @return $this
     */
    public function setSpace($space)
    {
        $this->space = $space;
        return $this;
    }

    /**
     * @return Space
     */
    public function getSpace()
    {
        return $this->space;
    }
}


