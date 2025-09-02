<?php

namespace App\Entity;

use App\Repository\ExamenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamenRepository::class)]
class Examen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $typeExamen = null; // code / créneau / conduite

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $datePassage = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $frais = 0.0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $statutExamen = false; // true = payé à temps

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lieu = null;   // 🔹 Nouveau champ

    #[ORM\OneToMany(
        targetEntity: CandidatExamen::class,
        mappedBy: 'examen',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $candidatExamens;

    public function __construct()
    {
        $this->candidatExamens = new ArrayCollection();
    }

    // 🔹 Getters / setters
    public function getId(): ?int { return $this->id; }

    public function getTypeExamen(): ?string { return $this->typeExamen; }
    public function setTypeExamen(string $typeExamen): self { $this->typeExamen = $typeExamen; return $this; }

    public function getDatePassage(): ?\DateTimeInterface { return $this->datePassage; }
    public function setDatePassage(\DateTimeInterface $datePassage): self { $this->datePassage = $datePassage; return $this; }

    public function getFrais(): float { return $this->frais; }
    public function setFrais(float $frais): self { $this->frais = $frais; return $this; }

    public function isStatutExamen(): bool { return $this->statutExamen; }
    public function setStatutExamen(bool $statutExamen): self { $this->statutExamen = $statutExamen; return $this; }

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(?string $lieu): self { $this->lieu = $lieu; return $this; }

    /** @return Collection<int, CandidatExamen> */
    public function getCandidatExamens(): Collection { return $this->candidatExamens; }

    public function addCandidatExamen(CandidatExamen $candidatExamen): self
    {
        if (!$this->candidatExamens->contains($candidatExamen)) {
            $this->candidatExamens[] = $candidatExamen;
            $candidatExamen->setExamen($this);
        }
        return $this;
    }

    public function removeCandidatExamen(CandidatExamen $candidatExamen): self
    {
        if ($this->candidatExamens->removeElement($candidatExamen)) {
            if ($candidatExamen->getExamen() === $this) {
                $candidatExamen->setExamen(null);
            }
        }
        return $this;
    }

    // 🔹 Logique de paiement
    public function payerExamen(\DateTimeInterface $datePaiement): self
    {
        if ($this->datePassage === null) {
            throw new \LogicException("Impossible de payer : la date de passage n’est pas définie.");
        }

        $limite = (clone $this->datePassage)->modify('-2 days');

        $this->statutExamen = $datePaiement <= $limite;

        return $this;
    }
}
