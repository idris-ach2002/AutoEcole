<?php

namespace App\Entity;

use App\Repository\ExamenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExamenRepository::class)]
#[ORM\Index(columns: ['type_examen', 'date_passage'], name: 'idx_examen_type_date')]
class Examen
{
    public const TYPE_CODE = 'code';
    public const TYPE_CRENEAU = 'creneau';
    public const TYPE_CONDUITE = 'conduite';
    public const TYPES = [self::TYPE_CODE, self::TYPE_CRENEAU, self::TYPE_CONDUITE];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::TYPES)]
    private ?string $typeExamen = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    private ?\DateTimeInterface $datePassage = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private float|string $frais = 0.0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $statutExamen = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lieu = null;

    /** @var Collection<int, CandidatExamen> */
    #[ORM\OneToMany(targetEntity: CandidatExamen::class, mappedBy: 'examen', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $candidatExamens;

    public function __construct()
    {
        $this->candidatExamens = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTypeExamen(): ?string { return $this->typeExamen; }
    public function setTypeExamen(string $typeExamen): self { $this->typeExamen = $typeExamen; return $this; }

    public function getTypeLabel(): string
    {
        return match ($this->typeExamen) {
            self::TYPE_CODE => 'Code',
            self::TYPE_CRENEAU => 'Créneau',
            self::TYPE_CONDUITE => 'Conduite',
            default => ucfirst((string) $this->typeExamen),
        };
    }

    public function getDatePassage(): ?\DateTimeInterface { return $this->datePassage; }
    public function setDatePassage(\DateTimeInterface $datePassage): self { $this->datePassage = $datePassage; return $this; }

    public function getFrais(): float { return (float) $this->frais; }
    public function setFrais(float|string $frais): self { $this->frais = max(0.0, (float) $frais); return $this; }

    public function isStatutExamen(): bool { return $this->statutExamen; }
    public function setStatutExamen(bool $statutExamen): self { $this->statutExamen = $statutExamen; return $this; }

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(?string $lieu): self { $this->lieu = $lieu ? trim($lieu) : null; return $this; }

    /** @return Collection<int, CandidatExamen> */
    public function getCandidatExamens(): Collection { return $this->candidatExamens; }

    public function addCandidatExamen(CandidatExamen $candidatExamen): self
    {
        if (!$this->candidatExamens->contains($candidatExamen)) {
            $this->candidatExamens->add($candidatExamen);
            $candidatExamen->setExamen($this);
        }
        return $this;
    }

    public function removeCandidatExamen(CandidatExamen $candidatExamen): self
    {
        if ($this->candidatExamens->removeElement($candidatExamen) && $candidatExamen->getExamen() === $this) {
            $candidatExamen->setExamen(null);
        }
        return $this;
    }

    public function getInscritsCount(): int { return $this->candidatExamens->count(); }

    public function isPast(): bool
    {
        return $this->datePassage ? $this->datePassage < new \DateTimeImmutable('today') : false;
    }
}
